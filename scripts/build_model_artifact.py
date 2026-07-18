"""Build the Phase 6 production-ready Random Forest model artifact.

Run from the repository root:

    python scripts/build_model_artifact.py
"""

from __future__ import annotations

import hashlib
import io
import json
import sys
from datetime import datetime, timezone
from pathlib import Path

import joblib
import pandas as pd
import sklearn
from sklearn.ensemble import RandomForestClassifier
from sklearn.pipeline import Pipeline

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))

from model_artifact import validate_model_bundle
from model_contract import APPROVED_MODEL_FEATURES, QUALITY_LABELS, SENSORY_ENCODING

DATASET_PATH = ROOT / "data" / "milk_quality_eas67_2023_synth_1500.csv"
DATASET_METADATA_PATH = ROOT / "data" / "milk_quality_eas67_2023_synth_1500.metadata.json"
METRICS_PATH = ROOT / "reports" / "model-evaluation" / "metrics.json"
ARTIFACT_DIR = ROOT / "ml_model" / "artifacts"
ARTIFACT_PATH = ARTIFACT_DIR / "milk_quality_rf_v1.joblib"
ARTIFACT_METADATA_PATH = ARTIFACT_DIR / "milk_quality_rf_v1.metadata.json"
ARTIFACT_SHA256_PATH = ARTIFACT_DIR / "milk_quality_rf_v1.joblib.sha256"

MODEL_VERSION = "milk_quality_rf_v1"
ARTIFACT_SCHEMA_VERSION = "1.0"
RANDOM_STATE = 42


def build_pipeline() -> Pipeline:
    return Pipeline(
        steps=[
            (
                "classifier",
                RandomForestClassifier(
                    n_estimators=300,
                    max_depth=None,
                    min_samples_split=2,
                    min_samples_leaf=1,
                    random_state=RANDOM_STATE,
                    class_weight="balanced",
                    n_jobs=1,
                ),
            )
        ]
    )


def sha256_bytes(payload: bytes) -> str:
    return hashlib.sha256(payload).hexdigest()


def load_inputs() -> tuple[pd.DataFrame, dict, dict]:
    dataset = pd.read_csv(DATASET_PATH)
    dataset_metadata = json.loads(DATASET_METADATA_PATH.read_text())
    metrics = json.loads(METRICS_PATH.read_text())

    expected_columns = APPROVED_MODEL_FEATURES + ["Label"]
    if list(dataset.columns) != expected_columns:
        raise ValueError(f"Dataset columns do not match contract: {list(dataset.columns)}")
    if metrics["feature_order"] != APPROVED_MODEL_FEATURES:
        raise ValueError("Metrics feature order does not match approved contract.")
    if metrics["classes"] != QUALITY_LABELS:
        raise ValueError("Metrics classes do not match approved contract.")

    return dataset, dataset_metadata, metrics


def main() -> None:
    ARTIFACT_DIR.mkdir(parents=True, exist_ok=True)
    dataset, dataset_metadata, metrics = load_inputs()

    x = dataset[APPROVED_MODEL_FEATURES]
    y = dataset["Label"]

    pipeline = build_pipeline()
    pipeline.fit(x, y)

    model_buffer = io.BytesIO()
    joblib.dump(pipeline, model_buffer)
    model_payload_sha256 = sha256_bytes(model_buffer.getvalue())

    metadata = {
        "artifact_schema_version": ARTIFACT_SCHEMA_VERSION,
        "model_version": MODEL_VERSION,
        "model_type": "sklearn.pipeline.Pipeline(RandomForestClassifier)",
        "dataset_path": str(DATASET_PATH.relative_to(ROOT)),
        "dataset_version": dataset_metadata["dataset_version"],
        "label_policy_version": dataset_metadata["label_policy_version"],
        "feature_order": APPROVED_MODEL_FEATURES,
        "classes": QUALITY_LABELS,
        "sensory_encoding": SENSORY_ENCODING,
        "metrics": metrics["random_forest"],
        "dependencies": {
            **metrics["dependencies"],
            "joblib": joblib.__version__,
            "scikit_learn": sklearn.__version__,
        },
        "training_timestamp": datetime.now(timezone.utc).isoformat(),
        "random_state": RANDOM_STATE,
        "artifact_payload_sha256": model_payload_sha256,
    }

    bundle = {
        "model": pipeline,
        "metadata": metadata,
    }
    validate_model_bundle(bundle)
    joblib.dump(bundle, ARTIFACT_PATH)

    artifact_file_sha256 = sha256_bytes(ARTIFACT_PATH.read_bytes())
    ARTIFACT_SHA256_PATH.write_text(f"{artifact_file_sha256}  {ARTIFACT_PATH.name}\n")

    metadata_with_file_hash = {
        **metadata,
        "artifact_file_sha256": artifact_file_sha256,
        "artifact_path": str(ARTIFACT_PATH.relative_to(ROOT)),
    }
    ARTIFACT_METADATA_PATH.write_text(json.dumps(metadata_with_file_hash, indent=2) + "\n")

    print(f"Wrote artifact to {ARTIFACT_PATH.relative_to(ROOT)}")
    print(f"Wrote metadata to {ARTIFACT_METADATA_PATH.relative_to(ROOT)}")
    print(f"Artifact SHA-256: {artifact_file_sha256}")


if __name__ == "__main__":
    main()
