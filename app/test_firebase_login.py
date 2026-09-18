import os

import requests
from dotenv import load_dotenv

load_dotenv()

API_KEY = os.environ.get("FIREBASE_API_KEY")
EMAIL = os.environ.get("FIREBASE_TEST_EMAIL")
PASSWORD = os.environ.get("FIREBASE_TEST_PASSWORD")

def firebase_login(email, password):
    url = f"https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={API_KEY}"
    payload = {
        "email": email,
        "password": password,
        "returnSecureToken": True
    }
    response = requests.post(url, json=payload)
    return response.json()

if __name__ == "__main__":
    missing = [
        name for name, value in {
            "FIREBASE_API_KEY": API_KEY,
            "FIREBASE_TEST_EMAIL": EMAIL,
            "FIREBASE_TEST_PASSWORD": PASSWORD,
        }.items()
        if not value
    ]
    if missing:
        raise SystemExit(f"Missing required environment variables: {', '.join(missing)}")

    result = firebase_login(EMAIL, PASSWORD)
    if "idToken" in result:
        print("Firebase login succeeded.")
    else:
        print("Firebase login failed:", result.get("error", {}).get("message", "Unknown error"))
