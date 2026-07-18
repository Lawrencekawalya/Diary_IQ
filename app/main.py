from flask import Flask, render_template, request, redirect, url_for, session
import firebase_admin
from firebase_admin import auth
from firebase_admin import credentials
import joblib
import pandas as pd
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
from model_contract import (
    LEGACY_MODEL_FEATURES,
    QUALITY_LABELS,
    QUALITY_MAP,
    normalize_quality_label,
)

# Configure logging
logging.basicConfig(
    filename="firestore_errors.log",
    level=logging.ERROR,
    format="%(asctime)s - %(levelname)s - %(message)s",
)

# Load standards.json
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CONFIG_PATH = os.path.join(BASE_DIR, "..", "config", "standards.json")

try:
    with open(CONFIG_PATH) as f:
        STANDARDS = {item["Parameter"]: item for item in json.load(f)}
except FileNotFoundError:
    print("⚠️ standards.json not found. Falling back to empty config.")
    STANDARDS = {}



app = Flask(__name__)
app.secret_key = os.environ.get('FLASK_SECRET_KEY')
if not app.secret_key:
    raise RuntimeError("FLASK_SECRET_KEY must be set in the environment.")
app.permanent_session_lifetime = timedelta(minutes=30)

# Initialize Firebase
# cred = credentials.Certificate("firebase/diaryiq-firebase-adminsdk-fbsvc-4465f48c80.json")
cred = credentials.Certificate("firebase/firebase_key.json")
firebase_admin.initialize_app(cred)

db = firestore.client()

# Load legacy baseline model until the thesis-aligned artifact is trained.
model = joblib.load("ml_model/dairy_model_legacy_9feature.pkl")
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

def safe_float(value, field_name):
    """Try to convert to float, return None or raise a friendly error."""
    try:
        return float(value)
    except ValueError:
        raise ValueError(f"Invalid entry for {field_name}. Please enter a numeric value.")

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
        history_data.append({
            "Batch Number": d.get("Batch Number"),
            "Number of Liters Collected": d.get("Number of Liters Collected", 0),
            "Collection Center": d.get("Collection Center"),
            "District": d.get("District"),
            "Location": d.get("Location"),
            "Tested By": d.get("Tested By"),
            "Time of Collection": d.get("Time of Collection"),
            "Prediction": normalize_quality_label(d.get("prediction")),
        })

        # Build chart data too
        if "Time of Collection" in d:
            chart_data.append({
                "date": d["Time of Collection"],
                "prediction": QUALITY_MAP.get(normalize_quality_label(d.get("prediction")), 0),
                "collection_center": d.get("Collection Center"),
                "prediction_label": normalize_quality_label(d.get("prediction")),
                "district": d.get("District"),
                "liters_collected": d.get("Number of Liters Collected", 0)
            })

            # chart_data.append({
            #     "date": d["Time of Collection"],
            #     "prediction": QUALITY_MAP.get(normalize_quality_label(d.get("prediction")), 0),
            #     "collection_center": d.get("Collection Center"),  # ✅ Fix here too
            #     "prediction_label": d.get("prediction"),
            # })

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

    # 1) Collect batch info
    batch_info = {
    'Collection Center': request.form.get('collection_center'),
    'Contact': request.form.get('contact'),
    'District': request.form.get('district'),
    'Location': request.form.get('location'),
    'Driver Name': request.form.get('driver_name'),
    'Transport Details': request.form.get('transport_details'),
    'Batch Number': f"BATCH-{uuid.uuid4().hex[:8].upper()}",
    'Time of Collection': datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
    'Tested By': request.form.get('tested_by'),
    'Number of Liters Collected': safe_float(request.form.get('liters_collected', 0), 'Number of Liters Collected'),
    }

    try:
        raw = {
            'pH':                 safe_float(request.form['ph'], 'pH Level'),
            'Temperature':        safe_float(request.form['temperature'], 'Temperature'),
            'Fat_Content':        safe_float(request.form['fat'], 'Fat Content'),
            'SNF':                safe_float(request.form['snf'], 'SNF'),
            'Titratable_Acidity': safe_float(request.form['acidity'], 'Titratable Acidity'),
            'Protein_Content':    safe_float(request.form['protein'], 'Protein Content'),
            'Lactose_Content':    safe_float(request.form['lactose'], 'Lactose Content'),
            'TPC':                safe_float(request.form['tpc'], 'Total Plate Count'),
            'SCC':                safe_float(request.form['scc'], 'Somatic Cell Count'),
        }
    except ValueError as e:
        logging.error(f"❌ User input error: {e}")

        # Re-render form with previous user input and error message
        return render_template(
            "index.html",
            error=f"⚠️ {e} Please enter only numeric values in all testing fields.",
            previous_inputs=request.form,      # pass all old values
            show_predictor=True                # signal to reopen the testing section
        )


    # 3) Run ML prediction
    df = pd.DataFrame([list(raw.values())], columns=list(raw.keys()))
    prediction = normalize_quality_label(model.predict(df)[0])

    # 4) Build standards observations separately from the ML prediction.
    colors = []
    standards_observations = []

    for feat, val in raw.items():
        rule = STANDARDS.get(feat)
        if not rule:   # parameter not in standards.json
            colors.append('#bdc3c7')  # neutral gray
            continue

        low, high = rule.get("Min"), rule.get("Max")
        in_range = True

        if low is not None and val < low:
            in_range = False
            standards_observations.append(f"Warning: {feat}: below normal ({val}) - {rule['Remarks']}")

        if high is not None and val > high:
            in_range = False
            standards_observations.append(f"Warning: {feat}: above normal ({val}) - {rule['Remarks']}")

        colors.append('#2ecc71' if in_range else '#e67e22')

    # 5) Default observations if all configured parameters are normal.
    if not standards_observations:
        standards_observations.append("Milk meets the configured standards thresholds.")
        standards_observations.append("Maintain current handling procedures.")

    # 6) Save to Firestore
    batch_doc = {
        **batch_info,
        **raw,
        "prediction": prediction,
        "colors": colors,
        "standards_observations": standards_observations,
        "suggestions": standards_observations,
        "created_at": firestore.SERVER_TIMESTAMP
    }

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
        chart_data.append({
            "date": d["Time of Collection"],
            "prediction": QUALITY_MAP.get(normalize_quality_label(d.get("prediction")), 0),
            "Collection Center": d.get("Collection Center"),
            "prediction_label": normalize_quality_label(d.get("prediction"))
        })
    # Only show parameters entered by user
    visible_fields = LEGACY_MODEL_FEATURES

    # Render template
    return render_template(
        "result.html",
        prediction=normalize_quality_label(data.get("prediction")),
        feature_names=visible_fields,
        raw_values=[data.get(k) for k in visible_fields],
        colors=data.get("colors", []),
        raw={k: data.get(k) for k in visible_fields},
        suggestions=data.get("standards_observations", data.get("suggestions", [])),
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
