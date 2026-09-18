import json
from pathlib import Path

import pandas as pd

from model_contract import APPROVED_MODEL_FEATURES, QUALITY_LABELS


DATASET_PATH = Path("data/milk_quality_eas67_2023_synth_1500.csv")
METADATA_PATH = Path("data/milk_quality_eas67_2023_synth_1500.metadata.json")


def test_dataset_schema_matches_approved_contract():
    df = pd.read_csv(DATASET_PATH)

    assert list(df.columns) == APPROVED_MODEL_FEATURES + ["Label"]
    assert df.shape == (1500, 12)
    assert "SNF" not in df.columns
    assert "Turbidity" not in df.columns


def test_dataset_class_balance_and_quality():
    df = pd.read_csv(DATASET_PATH)

    assert df["Label"].value_counts().reindex(QUALITY_LABELS).to_dict() == {
        "Low": 500,
        "Medium": 500,
        "High": 500,
    }
    assert int(df.isna().sum().sum()) == 0
    assert int(df.duplicated(subset=APPROVED_MODEL_FEATURES).sum()) == 0
    assert {column: sorted(df[column].unique().tolist()) for column in ["Taste", "Odor", "Color"]} == {
        "Taste": [0, 1],
        "Odor": [0, 1],
        "Color": [0, 1],
    }


def test_dataset_metadata_matches_dataset():
    df = pd.read_csv(DATASET_PATH)
    metadata = json.loads(METADATA_PATH.read_text())

    assert metadata["dataset_version"] == "milk_quality_eas67_2023_synth_1500_v1"
    assert metadata["random_seed"] == 20260718
    assert metadata["row_count"] == len(df)
    assert metadata["features"] == APPROVED_MODEL_FEATURES
    assert metadata["class_distribution"] == {
        "Low": 500,
        "Medium": 500,
        "High": 500,
    }
