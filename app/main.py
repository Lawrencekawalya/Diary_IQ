from flask import Flask, render_template, request, redirect, url_for, session
import firebase_admin
from firebase_admin import auth
from firebase_admin import credentials
from datetime import datetime, timedelta
import os
import requests
import uuid
import firebase_admin
from firebase_admin import credentials, firestore
from flask import redirect, url_for
import secrets
from dotenv import load_dotenv
load_dotenv()
import json
import time
import logging
import google.api_core.exceptions

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.abspath(os.path.join(BASE_DIR, ".."))
if PROJECT_ROOT not in os.sys.path:
    os.sys.path.insert(0, PROJECT_ROOT)

from model_contract import (
    APPROVED_MODEL_FEATURES,
    QUALITY_LABELS,
    QUALITY_MAP,
    normalize_quality_label,
)
from model_artifact import load_model_bundle
from prediction_service import predict_milk_quality
from database_records import (
    build_batch_info,
    build_prediction_record,
    chart_point_from_record,
    history_row_from_record,
)

# Configure logging
logging.basicConfig(
    filename="firestore_errors.log",
    level=logging.ERROR,
    format="%(asctime)s - %(levelname)s - %(message)s",
)

CONFIG_PATH = os.path.join(PROJECT_ROOT, "config", "standards.json")

try:
    with open(CONFIG_PATH) as f:
        STANDARDS = {item["Parameter"]: item for item in json.load(f)}
except FileNotFoundError:
    print("⚠️ standards.json not found. Falling back to empty config.")
    STANDARDS = {}



app = Flask(
    __name__,
    template_folder=os.path.join(BASE_DIR, "templates"),
    static_folder=os.path.join(BASE_DIR, "static"),
)
app.secret_key = os.environ.get('FLASK_SECRET_KEY')
if not app.secret_key:
    raise RuntimeError("FLASK_SECRET_KEY must be set in the environment.")
app.permanent_session_lifetime = timedelta(minutes=30)

# Initialize Firebase
# cred = credentials.Certificate("firebase/diaryiq-firebase-adminsdk-fbsvc-4465f48c80.json")
cred = credentials.Certificate("firebase/firebase_key.json")
firebase_admin.initialize_app(cred)

db = firestore.client()

MODEL_ARTIFACT_PATH = os.path.join(PROJECT_ROOT, "ml_model", "artifacts", "milk_quality_rf_v1.joblib")
model_bundle = load_model_bundle(MODEL_ARTIFACT_PATH)
model = model_bundle["model"]
model_metadata = model_bundle["metadata"]
labels = QUALITY_LABELS

FIREBASE_API_KEY = os.environ.get("FIREBASE_API_KEY")

if not FIREBASE_API_KEY:
    print("API KEY is missing, please set FIREBASE_API_KEY")
else:
    print("Firebase API key is configured")


def firebase_login(email, password):
    url = f"https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={FIREBASE_API_KEY}"
    payload = {
        "email": email,
        "password": password,
        "returnSecureToken": True
    }
    response = requests.post(url, json=payload)
    return response.json()

# Show login form
@app.route('/')
def login_page():
    return render_template('login.html')

@app.route('/index')
def index():
    if 'user' not in session:
        return redirect(url_for('login_page'))
    return render_template('index.html')

# Handle login form submission
@app.route('/login', methods=['POST'])
def login():
    email = request.form['username']
    password = request.form['password']

    result = firebase_login(email, password)

    if "idToken" in result:
        session.permanent = True
        session['user'] = result['email']
        return redirect(url_for('index'))
    else:
        error_message = result.get("error", {}).get("message", "Invalid credentials")
        return render_template('login.html', error=error_message)
    
# Logout
@app.route('/logout')
def logout():
    session.pop('user', None)
    return redirect(url_for('login_page'))

#     return render_template("history.html", history_data=history_data, chart_data=chart_data)
@app.route('/history')
def history():
    if 'user' not in session:
        return redirect(url_for('login_page'))

    # Fetch all batches ordered by created_at
    batches = db.collection("milk_batches").order_by("created_at").stream()

    history_data = []
    chart_data = []
    for batch in batches:
        d = batch.to_dict()
        history_data.append(history_row_from_record(d))

        # Build chart data too
        chart_point = chart_point_from_record(d, QUALITY_MAP)
        if chart_point:
            chart_data.append(chart_point)

    return render_template("history.html", history_data=history_data, chart_data=chart_data)



@app.route('/debug/firebase')
def debug_firebase():
    # Project ID from Firebase Admin SDK
    project_id = None
    try:
        app_options = firebase_admin.get_app().project_id
        project_id = app_options
    except Exception as e:
        project_id = f"Error reading project_id: {e}"

    return {
        "firebase_admin_project_id": project_id,
        "firebase_api_key_configured": bool(FIREBASE_API_KEY)
    }
############################################################################
# QUALITY_MAP comes from the approved model contract.

@app.route('/predict', methods=['POST'])
def predict():
    if 'user' not in session:
        return redirect(url_for('login_page'))

    try:
        batch_info = build_batch_info(
            request.form,
            batch_number=f"BATCH-{uuid.uuid4().hex[:8].upper()}",
            collected_at=datetime.now(),
        )
        prediction_result = predict_milk_quality(request.form, model, model_metadata, STANDARDS)
    except ValueError as e:
        logging.error(f"❌ User input error: {e}")

        # Re-render form with previous user input and error message
        return render_template(
            "index.html",
            error=f"⚠️ {e}",
            previous_inputs=request.form,      # pass all old values
            show_predictor=True                # signal to reopen the testing section
        )

    batch_doc = build_prediction_record(batch_info, prediction_result, firestore.SERVER_TIMESTAMP)

    # Firestore write with automatic retry and error logging
    max_retries = 3
    retry_delay = 3  # seconds

    for attempt in range(max_retries):
        try:
            doc_ref = db.collection("milk_batches").add(batch_doc)
            print(f"✅ Firestore write successful on attempt {attempt + 1}")
            logging.info(f"✅ Firestore write successful on attempt {attempt + 1}")
            break  # success → exit loop

        except google.api_core.exceptions.ServiceUnavailable as e:
            # Log error to file
            logging.error(f"⚠️ Firestore unavailable (attempt {attempt + 1}): {e}")

            if attempt < max_retries - 1:
                print(f"⚠️ Retrying Firestore connection in {retry_delay} seconds...")
                time.sleep(retry_delay)
                continue
            else:
                print("❌ Firestore still unavailable after retries.")
                logging.error("❌ Firestore still unavailable after retries.")
                return render_template(
                    "index.html",
                    error="Firestore connection failed. Please check your internet and try again.",
                )

        except Exception as e:
            print(f"❌ Unexpected Firestore error: {e}")
            logging.error(f"❌ Unexpected Firestore error: {e}")
            return render_template(
                "index.html",
                error="An unexpected error occurred while saving data. Please try again.",
            )

    # ✅ Only reach this point if Firestore succeeded
    batch_id = doc_ref[1].id

    # 7) Redirect to result page
    return redirect(url_for('show_result', batch_id=batch_id))
# ###############################################################################
@app.route('/result/<batch_id>')
def show_result(batch_id):
    if 'user' not in session:
        return redirect(url_for('login_page'))

    # Get this batch
    doc = db.collection("milk_batches").document(batch_id).get()
    if not doc.exists:
        return "Batch not found", 404
    data = doc.to_dict()

    # Fetch history for chart
    batches = db.collection("milk_batches").order_by("created_at").stream()
    chart_data = []
    for batch in batches:
        d = batch.to_dict()
        if "Time of Collection" not in d:
            continue
        chart_point = chart_point_from_record(d, QUALITY_MAP)
        if chart_point:
            chart_data.append(chart_point)
    # Only show parameters entered by user
    visible_fields = APPROVED_MODEL_FEATURES

    # Render template
    return render_template(
        "result.html",
        prediction=normalize_quality_label(data.get("prediction")),
        feature_names=visible_fields,
        raw_values=[data.get(k) for k in visible_fields],
        colors=data.get("colors", []),
        raw={k: data.get(k) for k in visible_fields},
        suggestions=data.get("standards_observations", data.get("suggestions", [])),
        confidence=data.get("confidence"),
        probabilities=data.get("probabilities") or {},
        model_metadata=data.get("model_metadata") or {},
        batch_info = {
        "Collection Center": data.get("Collection Center"),
        "Contact": data.get("Contact"),
        "District": data.get("District"),
        "Location": data.get("Location"),
        "Driver Name": data.get("Driver Name"),
        "Vehicle Number Plate": data.get("Transport Details"),
        "Batch Number": data.get("Batch Number"),
        "Time of Collection": data.get("Time of Collection"),
        "Tested By": data.get("Tested By"),
        "Number of Liters Collected": data.get("Number of Liters Collected"),
        },
        chart_data=chart_data,
        STANDARDS=STANDARDS
    )

if __name__ == '__main__':
    app.run(debug=True)
