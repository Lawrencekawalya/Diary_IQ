import json
from pathlib import Path

import pytest

from model_artifact import load_model_bundle
from model_contract import APPROVED_MODEL_FEATURES, QUALITY_LABELS
from prediction_service import build_feature_frame, parse_model_inputs, predict_milk_quality


def valid_form():
    return {
        "ph": "6.70",
        "temperature": "4.0",
        "taste": "normal",
        "odor": "fresh",
        "fat": "3.8",
        "acidity": "0.15",
        "protein": "3.3",
        "lactose": "4.8",
        "tpc": "50000",
        "scc": "200000",
        "color": "normal",
    }


def standards():
    return {item["Parameter"]: item for item in json.loads(Path("config/standards.json").read_text())}


def artifact_bundle():
    return load_model_bundle("ml_model/artifacts/milk_quality_rf_v1.joblib")


def test_parse_model_inputs_and_feature_order():
    raw, sensory_inputs, encoded_sensory = parse_model_inputs(valid_form())
    frame = build_feature_frame(raw)

    assert list(raw) == APPROVED_MODEL_FEATURES
    assert list(frame.columns) == APPROVED_MODEL_FEATURES
    assert sensory_inputs == {"Taste": "normal", "Odor": "fresh", "Color": "normal"}
    assert encoded_sensory == {"Taste": 1, "Odor": 1, "Color": 1}


def test_known_sample_prediction_returns_metadata_and_probabilities():
    bundle = artifact_bundle()
    result = predict_milk_quality(valid_form(), bundle["model"], bundle["metadata"], standards())

    assert result["prediction"] in QUALITY_LABELS
    assert result["prediction"] == "High"
    assert result["confidence"] == max(result["probabilities"].values())
    assert list(result["probabilities"]) == QUALITY_LABELS
    assert result["model_metadata"]["model_version"] == "milk_quality_rf_v1"
    assert result["model_metadata"]["dataset_version"] == "milk_quality_eas67_2023_synth_1500_v1"


def test_invalid_numeric_input_is_rejected():
    form = valid_form()
    form["ph"] = "not-a-number"

    with pytest.raises(ValueError, match="pH Level"):
        parse_model_inputs(form)


def test_missing_sensory_input_is_rejected():
    form = valid_form()
    del form["taste"]

    with pytest.raises(ValueError, match="Taste is required"):
        parse_model_inputs(form)
