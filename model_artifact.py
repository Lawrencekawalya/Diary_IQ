"""Model artifact loading and compatibility validation."""

from __future__ import annotations

from pathlib import Path
from typing import Any

import joblib

from model_contract import APPROVED_MODEL_FEATURES, QUALITY_LABELS, SENSORY_ENCODING

REQUIRED_METADATA_KEYS = {
    "artifact_schema_version",
    "model_version",
    "dataset_version",
    "label_policy_version",
    "feature_order",
    "classes",
    "sensory_encoding",
    "metrics",
    "dependencies",
    "training_timestamp",
    "artifact_payload_sha256",
}


class ModelArtifactError(RuntimeError):
    """Raised when a model artifact cannot be trusted by the application."""


def validate_model_bundle(bundle: dict[str, Any]) -> None:
    if not isinstance(bundle, dict):
        raise ModelArtifactError("Model artifact must be a dictionary bundle.")

    if "model" not in bundle or "metadata" not in bundle:
        raise ModelArtifactError("Model artifact must contain model and metadata keys.")

    metadata = bundle["metadata"]
    if not isinstance(metadata, dict):
        raise ModelArtifactError("Model artifact metadata must be a dictionary.")

    missing = sorted(REQUIRED_METADATA_KEYS - set(metadata))
    if missing:
        raise ModelArtifactError(f"Model artifact metadata is missing keys: {missing}")

    if metadata["feature_order"] != APPROVED_MODEL_FEATURES:
        raise ModelArtifactError(
            f"Model feature order mismatch. Expected {APPROVED_MODEL_FEATURES}, "
            f"got {metadata['feature_order']}."
        )

    if metadata["classes"] != QUALITY_LABELS:
        raise ModelArtifactError(
            f"Model classes mismatch. Expected {QUALITY_LABELS}, got {metadata['classes']}."
        )

    if metadata["sensory_encoding"] != SENSORY_ENCODING:
        raise ModelArtifactError("Model sensory encoding mismatch.")

    model = bundle["model"]
    if not hasattr(model, "predict"):
        raise ModelArtifactError("Model artifact object does not support predict().")


def load_model_bundle(path: str | Path) -> dict[str, Any]:
    bundle = joblib.load(path)
    validate_model_bundle(bundle)
    return bundle
