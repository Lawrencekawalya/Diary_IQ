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



app = Flask(__name__)
# generate a 16-byte random token (hex encoded)
# print(secrets.token_hex(16))
app.secret_key = os.environ.get('FLASK_SECRET_KEY', '3e59e99addb9052eb7da6ab9935e49c3')
app.permanent_session_lifetime = timedelta(minutes=30)

# Initialize Firebase
cred = credentials.Certificate("firebase/diaryiq-firebase-adminsdk-fbsvc-4465f48c80.json")
# cred = credentials.Certificate("firebase_key.json")
firebase_admin.initialize_app(cred)

db = firestore.client()

# Load model
model = joblib.load("ml_model/dairy_model_4class.pkl")
labels = ['Low', 'Moderate', 'High']

# Normal ranges
NORMAL_RANGES = {
    'pH':                   (6.6,   6.8),
    'Temperature':         (None,  4),
    'Fat_Content':         (3.25, None),
    'SNF':                  (8.25, None),
    'Titratable_Acidity':   (0.13, 0.17),
    'Protein_Content':      (3.0,   3.5),
    'Lactose_Content':      (4.5,   5.2),
    'TPC':                  (None, 100000),
    'SCC':                  (None, 400000),
}

# @app.route('/')
# def login_page():
#     return render_template('login.html')

# @app.route('/login', methods=['POST'])
# def login():
#     email = request.form['username']
#     password = request.form['password']
#     if email and password:
#         session.permanent = True
#         session['user'] = email
#         return redirect(url_for('index'))
#     return render_template('login.html', error="Invalid credentials")

# @app.route('/')
# def login_page():
#     return render_template('login.html')

FIREBASE_API_KEY = os.environ.get("FIREBASE_API_KEY")

if not FIREBASE_API_KEY:
    print("API KEY is missing, please set FIREBASE_API_KEY")
else:
    print("API KEY loaded successfully")


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


# @app.route('/login', methods=['POST'])
# def login():
#     email = request.form['username']
#     password = request.form['password']

#     result = firebase_login(email, password)

#     if "idToken" in result:
#         session.permanent = True
#         session['user'] = result['email']
#         return redirect(url_for('index'))
#     else:
#         return render_template('login.html', error="Invalid credentials")


# FIREBASE_API_KEY = os.environ.get("FIREBASE_API_KEY")

# def firebase_login(email, password):
#     url = f"https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={FIREBASE_API_KEY}"
#     payload = {
#         "email": email,
#         "password": password,
#         "returnSecureToken": True
#     }
#     response = requests.post(url, json=payload)
#     return response.json()

# @app.route('/login', methods=['POST'])
# def login():
#     email = request.form['username']
#     password = request.form['password']

#     result = firebase_login(email, password)

#     if "idToken" in result:
#         session.permanent = True
#         session['user'] = result['email']
#         return redirect(url_for('index'))
#     else:
#         return render_template('login.html', error="Invalid credentials")


# @app.route('/logout')
# def logout():
#     session.pop('user', None)
#     return redirect(url_for('login'))

@app.route('/index')
def index():
    if 'user' not in session:
        return redirect(url_for('login'))
    return render_template('index.html')

@app.route('/history')
def history():
    if 'user' not in session:
        return redirect(url_for('login'))

    # Fetch all batches ordered by created_at
    batches = db.collection("milk_batches").order_by("created_at").stream()

    history_data = []
    for batch in batches:
        d = batch.to_dict()
        history_data.append({
            "Farmer": d.get("Farmer"),
            "Batch Number": d.get("Batch Number"),
            "Time of Collection": d.get("Time of Collection"),
            "Location": d.get("Location"),
            "Prediction": d.get("prediction"),
        })

    return render_template("history.html", history_data=history_data)

@app.route('/debug/firebase')
def debug_firebase():
    # Project ID from Firebase Admin SDK
    project_id = None
    try:
        app_options = firebase_admin.get_app().project_id
        project_id = app_options
    except Exception as e:
        project_id = f"Error reading project_id: {e}"

    # API Key from environment
    api_key = os.environ.get("FIREBASE_API_KEY", "⚠️ Not Set")

    return {
        "firebase_admin_project_id": project_id,
        "firebase_api_key": api_key
    }
############################################################################
QUALITY_MAP = {"Low": 0, "Moderate": 1, "High": 2}

@app.route('/predict', methods=['POST'])
def predict():
    if 'user' not in session:
        return redirect(url_for('login'))

    # 1) Collect batch info
    batch_info = {
        'Farmer': request.form.get('farmer'),
        'Contact': request.form.get('contact'),
        'Location': request.form.get('location'),
        'Batch Number': f"BATCH-{uuid.uuid4().hex[:8].upper()}",
        'Time of Collection': datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        'Transport Details': request.form.get('transport_details'),
    }

    # 2) Collect predictor inputs
    raw = {
        'pH':                 float(request.form['ph']),
        'Temperature':        float(request.form['temperature']),
        'Fat_Content':        float(request.form['fat']),
        'SNF':                float(request.form['snf']),
        'Titratable_Acidity': float(request.form['acidity']),
        'Protein_Content':    float(request.form['protein']),
        'Lactose_Content':    float(request.form['lactose']),
        'TPC':                float(request.form['tpc']),
        'SCC':                float(request.form['scc']),
    }

    # 3) Run prediction
    df = pd.DataFrame([list(raw.values())], columns=list(raw.keys()))
    prediction = model.predict(df)[0]   # "Low", "Moderate", "High"

    # 4) Build color list (for later reuse)
    colors = []
    for feat, val in raw.items():
        low, high = NORMAL_RANGES[feat]
        is_normal = True
        if low is not None and val < low:
            is_normal = False
        if high is not None and val > high:
            is_normal = False
        colors.append('#2ecc71' if is_normal else '#e67e22')

    # 5) Suggestions (for later reuse)
    suggestions = []
    if raw['Temperature'] > 4:
        suggestions.append("⚠ Check cooling system – temperature above safe range.")
    if raw['SCC'] > 400000:
        suggestions.append("⚠ High SCC – possible mastitis, review herd health.")
    if raw['Fat_Content'] < 3.25:
        suggestions.append("⚠ Low fat content – check feed and nutrition.")
    if raw['TPC'] > 100000:
        suggestions.append("⚠ High bacterial count – review hygiene and storage.")
    if raw['pH'] < 6.6 or raw['pH'] > 6.8:
        suggestions.append("⚠ Abnormal pH – check for contamination or spoilage.")

    if not suggestions:
        suggestions.append("✅ Milk meets quality standards.")
        suggestions.append("✅ Maintain current handling procedures.")

    # 6) Save to Firestore
    batch_doc = {
        **batch_info,
        **raw,
        "prediction": prediction,
        "colors": colors,         # store colors for later use
        "suggestions": suggestions,
        "created_at": firestore.SERVER_TIMESTAMP
    }
    doc_ref = db.collection("milk_batches").add(batch_doc)
    batch_id = doc_ref[1].id  # Firestore returns (write_result, doc_ref)

    # 7) Redirect to dedicated result page
    return redirect(url_for('show_result', batch_id=batch_id))

##############################################################
@app.route('/result/<batch_id>')
def show_result(batch_id):
    if 'user' not in session:
        return redirect(url_for('login'))

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
            "prediction": QUALITY_MAP.get(d.get("prediction"), 0),
            "farmer": d.get("Farmer"),                 # ✅ from this batch
            "prediction_label": d.get("prediction")    # ✅ from this batch
        })
        
    # Render template
    return render_template(
        "result.html",
        prediction=data.get("prediction"),
        feature_names=list(NORMAL_RANGES.keys()),
        raw_values=[data.get(k) for k in NORMAL_RANGES.keys()],
        colors=data.get("colors", []),
        raw={k: data.get(k) for k in NORMAL_RANGES.keys()},
        suggestions=data.get("suggestions", []),
        batch_info={
            "Farmer": data.get("Farmer"),
            "Contact": data.get("Contact"),
            "Location": data.get("Location"),
            "Batch Number": data.get("Batch Number"),
            "Time of Collection": data.get("Time of Collection"),
            "Transport Details": data.get("Transport Details"),
        },
        chart_data=chart_data
    )

if __name__ == '__main__':
    app.run(debug=True)
