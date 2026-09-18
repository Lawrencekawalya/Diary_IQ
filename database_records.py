"""Firestore record shaping and legacy compatibility helpers."""

from __future__ import annotations

from datetime import datetime
from typing import Any, Mapping

from model_contract import APPROVED_MODEL_FEATURES, normalize_quality_label

SECRET_FIELD_NAMES = {
    "password",
    "idToken",
    "refreshToken",
    "firebase_api_key",
    "FIREBASE_API_KEY",
}


def ensure_no_secret_fields(record: Mapping[str, Any]) -> None:
    found = sorted(field for field in SECRET_FIELD_NAMES if field in record)
    if found:
        raise ValueError(f"Refusing to store secret/auth fields in Firestore record: {found}")


def build_batch_info(form: Mapping[str, Any], batch_number: str, collected_at: datetime) -> dict[str, Any]:
    liters_value = form.get("liters_collected", 0)
    try:
        liters_collected = float(liters_value)
    except ValueError as exc:
        raise ValueError("Invalid entry for Number of Liters Collected. Please enter a numeric value.") from exc

    return {
        "Collection Center": form.get("collection_center"),
        "Contact": form.get("contact"),
        "District": form.get("district"),
        "Location": form.get("location"),
        "Driver Name": form.get("driver_name"),
        "Transport Details": form.get("transport_details"),
        "Batch Number": batch_number,
        "Time of Collection": collected_at.strftime("%Y-%m-%d %H:%M:%S"),
        "Tested By": form.get("tested_by"),
        "Number of Liters Collected": liters_collected,
    }


def build_prediction_record(
    batch_info: Mapping[str, Any],
    prediction_result: Mapping[str, Any],
    server_timestamp: Any,
) -> dict[str, Any]:
    record = {
        **batch_info,
        **prediction_result["raw"],
        "sensory_inputs": prediction_result["sensory_inputs"],
        "encoded_sensory_values": prediction_result["encoded_sensory_values"],
        "ml_prediction": prediction_result.get("ml_prediction", prediction_result["prediction"]),
        "prediction": prediction_result["prediction"],
        "probabilities": prediction_result["probabilities"],
        "confidence": prediction_result["confidence"],
        "standards_quality_gate": prediction_result.get("standards_quality_gate", {}),
        "model_metadata": prediction_result["model_metadata"],
        "standards_observations": prediction_result["standards_observations"],
        "colors": prediction_result["colors"],
        "created_at": server_timestamp,
        "record_schema_version": "milk_batch_prediction_v1",
    }
    ensure_no_secret_fields(record)
    return record


def legacy_safe_model_metadata(record: Mapping[str, Any]) -> dict[str, Any]:
    metadata = record.get("model_metadata")
    return metadata if isinstance(metadata, dict) else {}


def history_row_from_record(record: Mapping[str, Any], document_id: str | None = None) -> dict[str, Any]:
    confidence = record.get("confidence")
    if not isinstance(confidence, (int, float)):
        confidence = ""

    metadata = legacy_safe_model_metadata(record)
    return {
        "Document ID": document_id,
        "Batch Number": record.get("Batch Number"),
        "Number of Liters Collected": record.get("Number of Liters Collected", 0),
        "Collection Center": record.get("Collection Center"),
        "District": record.get("District"),
        "Location": record.get("Location"),
        "Tested By": record.get("Tested By"),
        "Time of Collection": record.get("Time of Collection"),
        "Prediction": normalize_quality_label(record.get("prediction")),
        **{feature: record.get(feature, "") for feature in APPROVED_MODEL_FEATURES},
        "Confidence": confidence,
        "Model Version": metadata.get("model_version", ""),
        "Dataset Version": metadata.get("dataset_version", ""),
    }


def parse_history_timestamp(value: Any) -> datetime:
    if isinstance(value, datetime):
        return value
    if not isinstance(value, str):
        return datetime.min

    for date_format in ("%Y-%m-%d %H:%M:%S", "%Y-%m-%dT%H:%M:%S"):
        try:
            return datetime.strptime(value, date_format)
        except ValueError:
            continue
    return datetime.min


def chart_point_from_record(record: Mapping[str, Any], quality_map: Mapping[str, int]) -> dict[str, Any] | None:
    if "Time of Collection" not in record:
        return None

    prediction = normalize_quality_label(record.get("prediction"))
    return {
        "date": record["Time of Collection"],
        "sort_key": parse_history_timestamp(record["Time of Collection"]).isoformat(),
        "prediction": quality_map.get(prediction, 0),
        "collection_center": record.get("Collection Center"),
        "prediction_label": prediction,
        "district": record.get("District"),
        "liters_collected": record.get("Number of Liters Collected", 0),
    }
