from copy import deepcopy

import pytest

from model_artifact import ModelArtifactError, load_model_bundle, validate_model_bundle
from model_contract import APPROVED_MODEL_FEATURES, QUALITY_LABELS, SENSORY_ENCODING


ARTIFACT_PATH = "ml_model/artifacts/milk_quality_rf_v1.joblib"


def test_model_artifact_loads_and_matches_contract():
    bundle = load_model_bundle(ARTIFACT_PATH)
    metadata = bundle["metadata"]

    assert metadata["feature_order"] == APPROVED_MODEL_FEATURES
    assert metadata["classes"] == QUALITY_LABELS
    assert metadata["sensory_encoding"] == SENSORY_ENCODING
    assert metadata["model_version"] == "milk_quality_rf_v1"
    assert hasattr(bundle["model"], "predict")
    assert hasattr(bundle["model"], "predict_proba")


def test_incompatible_model_artifact_is_rejected():
    bundle = load_model_bundle(ARTIFACT_PATH)
    bad_bundle = deepcopy(bundle)
    bad_bundle["metadata"]["classes"] = ["Low", "Moderate", "High"]

    with pytest.raises(ModelArtifactError):
        validate_model_bundle(bad_bundle)
