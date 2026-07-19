import json
from pathlib import Path

from ml_service import create_app
from model_artifact import load_model_bundle


API_TOKEN = "test-token"


def valid_api_payload():
    return {
        "pH": 6.70,
        "Temperature": 4.0,
        "Taste": "normal",
        "Odor": "fresh",
        "Fat_Content": 3.8,
        "Titratable_Acidity": 0.15,
        "Protein_Content": 3.3,
        "Lactose_Content": 4.8,
        "TPC": 50000,
        "SCC": 200000,
        "Color": "normal",
    }


def medium_api_payload():
    payload = valid_api_payload()
    payload.update(
        {
            "pH": 6.65,
            "Temperature": 8.5,
            "Fat_Content": 3.2,
            "Titratable_Acidity": 0.18,
            "Protein_Content": 3.2,
            "Lactose_Content": 4.6,
            "TPC": 1800000,
            "SCC": 400000,
        }
    )

    return payload


def low_api_payload():
    return {
        "pH": 2.0,
        "Temperature": 2.0,
        "Taste": "abnormal",
        "Odor": "fresh",
        "Fat_Content": 1.0,
        "Titratable_Acidity": 2.0,
        "Protein_Content": 1.0,
        "Lactose_Content": 1.0,
        "TPC": 1.0,
        "SCC": 2.0,
        "Color": "abnormal",
    }


def standards():
    return {item["Parameter"]: item for item in json.loads(Path("config/standards.json").read_text())}


def client():
    bundle = load_model_bundle("ml_model/artifacts/milk_quality_rf_v1.joblib")
    app = create_app(model_bundle=bundle, standards=standards(), api_token=API_TOKEN)

    return app.test_client()


def auth_headers():
    return {"Authorization": f"Bearer {API_TOKEN}"}


def test_health_endpoint_reports_loaded_model():
    response = client().get("/api/health")

    assert response.status_code == 200
    assert response.json["status"] == "ok"
    assert response.json["model_loaded"] is True
    assert response.json["model_version"] == "milk_quality_rf_v1"
    assert response.json["feature_count"] == 11


def test_predict_endpoint_returns_laravel_ready_prediction_response():
    response = client().post("/api/predict", json=valid_api_payload(), headers=auth_headers())

    assert response.status_code == 200
    assert response.json["prediction"] == "High"
    assert response.json["ml_prediction"] == "High"
    assert response.json["confidence"] == max(response.json["probabilities"].values())
    assert list(response.json["probabilities"]) == ["Low", "Medium", "High"]
    assert response.json["standards_quality_gate"]["applied"] is False
    assert response.json["model_metadata"]["model_version"] == "milk_quality_rf_v1"
    assert response.json["raw"]["pH"] == 6.7
    assert response.json["encoded_sensory_values"] == {"Color": 1, "Odor": 1, "Taste": 1}
    assert len(response.json["feature_status"]) == 11
    assert response.json["colors"] == response.json["feature_status"]


def test_predict_endpoint_returns_known_high_medium_and_low_samples():
    test_client = client()

    samples = [
        (valid_api_payload(), "High"),
        (medium_api_payload(), "Medium"),
        (low_api_payload(), "Low"),
    ]

    for payload, expected_prediction in samples:
        response = test_client.post("/api/predict", json=payload, headers=auth_headers())

        assert response.status_code == 200
        assert response.json["prediction"] == expected_prediction


def test_predict_endpoint_requires_bearer_token():
    response = client().post("/api/predict", json=valid_api_payload())

    assert response.status_code == 401
    assert response.json["error"] == "Unauthorized ML service request."


def test_predict_endpoint_rejects_missing_approved_features():
    payload = valid_api_payload()
    del payload["Color"]

    response = client().post("/api/predict", json=payload, headers=auth_headers())

    assert response.status_code == 422
    assert response.json["error"] == "Missing model features: Color"


def test_predict_endpoint_rejects_unexpected_model_features():
    payload = valid_api_payload()
    payload["SNF"] = 8.5

    response = client().post("/api/predict", json=payload, headers=auth_headers())

    assert response.status_code == 422
    assert response.json["error"] == "Unexpected model features: SNF"


def test_predict_endpoint_rejects_invalid_json_body():
    response = client().post(
        "/api/predict",
        data="not-json",
        headers={**auth_headers(), "Content-Type": "application/json"},
    )

    assert response.status_code == 400
    assert response.json["error"] == "Request body must be a JSON object."
