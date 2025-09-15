import requests

# 🔑 Paste your API key here (from firebaseConfig.apiKey)
API_KEY = "AIzaSyAr-g2Ql6vbXvh8jOQmfZ2bavbN2t5Cf_8"

# 👤 Replace with an actual user you created in Firebase Authentication
EMAIL = "emmanuelbeha@gmail.com"
PASSWORD = "beha*20!("

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
    result = firebase_login(EMAIL, PASSWORD)
    print("Response from Firebase:")
    print(result)
