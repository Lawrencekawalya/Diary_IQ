from datetime import datetime

import pytest

from database_records import (
    build_batch_info,
    build_prediction_record,
    chart_point_from_record,
    ensure_no_secret_fields,
    history_row_from_record,
    parse_history_timestamp,
)
from model_contract import APPROVED_MODEL_FEATURES


def sample_form():
    return {
        "collection_center": "Center A",
        "contact": "0700000000",
        "district": "Kampala",
        "location": "Nakawa",
        "driver_name": "Driver One",
        "transport_details": "UAX 123A",
        "tested_by": "Chemist One",
        "liters_collected": "120.5",
    }


def sample_prediction_result():
    raw = {
        "pH": 6.7,
        "Temperature": 4.0,
        "Taste": 1,
        "Odor": 1,
        "Fat_Content": 3.8,
        "Titratable_Acidity": 0.15,
        "Protein_Content": 3.3,
        "Lactose_Content": 4.8,
        "TPC": 50000.0,
        "SCC": 200000.0,
        "Color": 1,
    }
    return {
        "raw": raw,
        "sensory_inputs": {
            "Taste": "normal",
            "Odor": "fresh",
            "Color": "normal",
        },
        "encoded_sensory_values": {
            "Taste": 1,
            "Odor": 1,
            "Color": 1,
        },
        "prediction": "High",
        "ml_prediction": "High",
        "confidence": 1.0,
        "probabilities": {
            "Low": 0.0,
            "Medium": 0.0,
            "High": 1.0,
        },
        "standards_quality_gate": {
            "applied": False,
            "ml_prediction": "High",
            "final_prediction": "High",
            "max_allowed_quality": "High",
            "failed_features": [],
            "critical_features": [],
            "reason": "No standards gate downgrade was required.",
        },
        "model_metadata": {
            "model_version": "milk_quality_rf_v1",
            "dataset_version": "milk_quality_eas67_2023_synth_1500_v1",
            "label_policy_version": "eas67_codex_fao_policy_v1",
            "feature_order": APPROVED_MODEL_FEATURES,
            "classes": ["Low", "Medium", "High"],
        },
        "colors": ["#2ecc71"] * len(APPROVED_MODEL_FEATURES),
        "standards_observations": ["Milk meets the configured standards thresholds."],
    }


def test_build_prediction_record_contains_phase9_shape():
    batch_info = build_batch_info(sample_form(), "BATCH-TEST", datetime(2026, 7, 18, 12, 0, 0))
    record = build_prediction_record(batch_info, sample_prediction_result(), "SERVER_TIMESTAMP")

    for feature in APPROVED_MODEL_FEATURES:
        assert feature in record

    assert record["sensory_inputs"]["Taste"] == "normal"
    assert record["encoded_sensory_values"]["Taste"] == 1
    assert record["prediction"] == "High"
    assert record["ml_prediction"] == "High"
    assert record["standards_quality_gate"]["applied"] is False
    assert record["probabilities"]["High"] == 1.0
    assert record["confidence"] == 1.0
    assert record["model_metadata"]["model_version"] == "milk_quality_rf_v1"
    assert record["standards_observations"]
    assert record["created_at"] == "SERVER_TIMESTAMP"
    assert record["record_schema_version"] == "milk_batch_prediction_v1"


def test_secret_fields_are_rejected():
    with pytest.raises(ValueError):
        ensure_no_secret_fields({"idToken": "secret"})


def test_legacy_history_record_is_safe():
    row = history_row_from_record(
        {
            "Batch Number": "OLD-1",
            "prediction": "Moderate",
            "Time of Collection": "2026-07-18 12:00:00",
        },
        document_id="LEGACYDOC",
    )

    assert row["Document ID"] == "LEGACYDOC"
    assert row["Prediction"] == "Medium"
    assert row["Taste"] == ""
    assert row["Model Version"] == ""


def test_chart_point_supports_legacy_prediction_label():
    chart_point = chart_point_from_record(
        {
            "Time of Collection": "2026-07-18 12:00:00",
            "prediction": "Moderate",
        },
        {"Low": 0, "Medium": 1, "High": 2},
    )

    assert chart_point["prediction"] == 1
    assert chart_point["prediction_label"] == "Medium"
    assert chart_point["sort_key"] == "2026-07-18T12:00:00"


def test_parse_history_timestamp_handles_invalid_values():
    assert parse_history_timestamp("not-a-date").year == 1
