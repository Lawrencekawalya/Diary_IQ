"""Internal Python ML API for Laravel DairyIQ integration."""

from __future__ import annotations

import json
import os
from pathlib import Path
from typing import Any, Mapping

from flask import Flask, jsonify, request

from model_artifact import ModelArtifactError, load_model_bundle
from prediction_service import normalize_api_payload, predict_milk_quality

PROJECT_ROOT = Path(__file__).resolve().parent
DEFAULT_MODEL_ARTIFACT_PATH = PROJECT_ROOT / "ml_model" / "artifacts" / "milk_quality_rf_v1.joblib"
DEFAULT_STANDARDS_PATH = PROJECT_ROOT / "config" / "standards.json"


def load_standards(path: str | Path = DEFAULT_STANDARDS_PATH) -> dict[str, dict[str, Any]]:
    return {item["Parameter"]: item for item in json.loads(Path(path).read_text())}


def _authorized(headers: Mapping[str, str], token: str) -> bool:
    expected = f"Bearer {token}"

    return headers.get("Authorization") == expected


def create_app(
    *,
    model_bundle: dict[str, Any] | None = None,
    standards: dict[str, dict[str, Any]] | None = None,
    api_token: str | None = None,
    artifact_path: str | Path = DEFAULT_MODEL_ARTIFACT_PATH,
) -> Flask:
    app = Flask(__name__)
    app.json.sort_keys = False

    resolved_token = api_token or os.environ.get("ML_SERVICE_TOKEN")
    if not resolved_token:
        raise RuntimeError("ML_SERVICE_TOKEN must be set for the Python ML service.")

    try:
        resolved_bundle = model_bundle or load_model_bundle(artifact_path)
    except ModelArtifactError:
        raise
    except Exception as exc:
        raise ModelArtifactError(f"Unable to load model artifact: {exc}") from exc

    resolved_standards = standards or load_standards()
    model = resolved_bundle["model"]
    metadata = resolved_bundle["metadata"]

    @app.get("/api/health")
    def health():
        return jsonify(
            {
                "status": "ok",
                "model_loaded": True,
                "model_version": metadata["model_version"],
                "feature_count": len(metadata["feature_order"]),
            }
        )

    @app.post("/api/predict")
    def predict():
        if not _authorized(request.headers, resolved_token):
            return jsonify({"error": "Unauthorized ML service request."}), 401

        payload = request.get_json(silent=True)
        if not isinstance(payload, dict):
            return jsonify({"error": "Request body must be a JSON object."}), 400

        try:
            form_payload = normalize_api_payload(payload)
            result = predict_milk_quality(form_payload, model, metadata, resolved_standards)
        except ValueError as exc:
            return jsonify({"error": str(exc)}), 422

        return jsonify(
            {
                "prediction": result["prediction"],
                "ml_prediction": result["ml_prediction"],
                "confidence": result["confidence"],
                "probabilities": result["probabilities"],
                "standards_observations": result["standards_observations"],
                "standards_quality_gate": result["standards_quality_gate"],
                "model_metadata": result["model_metadata"],
                "feature_status": result["colors"],
                "colors": result["colors"],
                "raw": result["raw"],
                "sensory_inputs": result["sensory_inputs"],
                "encoded_sensory_values": result["encoded_sensory_values"],
            }
        )

    return app


if __name__ == "__main__":
    app = create_app()
    app.run(host=os.environ.get("ML_SERVICE_HOST", "127.0.0.1"), port=int(os.environ.get("ML_SERVICE_PORT", "5100")))
