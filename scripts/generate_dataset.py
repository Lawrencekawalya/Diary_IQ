"""Generate the thesis-aligned DairyIQ synthetic dataset.

The output is deterministic and standards-bounded for the approved 11-feature
contract. Run from the repository root:

    python scripts/generate_dataset.py
"""

from __future__ import annotations

import csv
import json
import random
import sys
from collections import Counter
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))

from model_contract import APPROVED_MODEL_FEATURES, QUALITY_LABELS

DATASET_PATH = ROOT / "data" / "milk_quality_eas67_2023_synth_1500.csv"
METADATA_PATH = ROOT / "data" / "milk_quality_eas67_2023_synth_1500.metadata.json"

RANDOM_SEED = 20260718
ROWS_PER_CLASS = 500
DATASET_VERSION = "milk_quality_eas67_2023_synth_1500_v1"
LABEL_POLICY_VERSION = "eas67_codex_fao_policy_v1"

FEATURE_UNITS = {
    "pH": "pH",
    "Temperature": "deg C",
    "Taste": "encoded binary, 1 normal/acceptable and 0 abnormal/off",
    "Odor": "encoded binary, 1 fresh/normal and 0 abnormal/objectionable",
    "Fat_Content": "%",
    "Titratable_Acidity": "% lactic acid",
    "Protein_Content": "%",
    "Lactose_Content": "%",
    "TPC": "CFU/ml",
    "SCC": "cells/ml",
    "Color": "encoded binary, 1 normal/creamy-white and 0 abnormal",
}

SOURCES = {
    "primary_standard": "US EAS 67:2023",
    "supplementary_sources": [
        "Codex CXC 57-2004 / CAC/RCP 57 milk hygiene guidance for temperature",
        "FAO milk composition reference ranges for protein and lactose",
    ],
}


def uniform(rng: random.Random, low: float, high: float, digits: int) -> float:
    return round(rng.uniform(low, high), digits)


def randint_rounded(rng: random.Random, low: int, high: int, step: int) -> int:
    value = rng.randrange(low // step, (high // step) + 1) * step
    return int(value)


def high_sample(rng: random.Random) -> dict[str, float | int | str]:
    return {
        "pH": uniform(rng, 6.60, 6.80, 2),
        "Temperature": uniform(rng, 2.0, 6.0, 1),
        "Taste": 1,
        "Odor": 1,
        "Fat_Content": uniform(rng, 3.30, 4.80, 2),
        "Titratable_Acidity": uniform(rng, 0.12, 0.17, 3),
        "Protein_Content": uniform(rng, 3.05, 3.50, 2),
        "Lactose_Content": uniform(rng, 4.55, 5.20, 2),
        "TPC": randint_rounded(rng, 10_000, 300_000, 100),
        "SCC": randint_rounded(rng, 20_000, 250_000, 100),
        "Color": 1,
        "Label": "High",
    }


def medium_sample(rng: random.Random) -> dict[str, float | int | str]:
    sample = {
        "pH": uniform(rng, 6.55, 6.85, 2),
        "Temperature": uniform(rng, 6.1, 10.0, 1),
        "Taste": 1,
        "Odor": 1,
        "Fat_Content": uniform(rng, 3.05, 3.40, 2),
        "Titratable_Acidity": uniform(rng, 0.16, 0.19, 3),
        "Protein_Content": uniform(rng, 2.85, 3.15, 2),
        "Lactose_Content": uniform(rng, 4.30, 4.65, 2),
        "TPC": randint_rounded(rng, 300_000, 2_000_000, 100),
        "SCC": randint_rounded(rng, 250_000, 450_000, 100),
        "Color": 1,
        "Label": "Medium",
    }

    defect = rng.choice(["Taste", "Odor", "Color", None, None])
    if defect:
        sample[defect] = 0

    return sample


def low_sample(rng: random.Random) -> dict[str, float | int | str]:
    sample = {
        "pH": rng.choice([uniform(rng, 6.20, 6.54, 2), uniform(rng, 6.86, 7.20, 2)]),
        "Temperature": uniform(rng, 10.1, 25.0, 1),
        "Taste": rng.choice([0, 0, 1]),
        "Odor": rng.choice([0, 0, 1]),
        "Fat_Content": uniform(rng, 2.00, 3.20, 2),
        "Titratable_Acidity": uniform(rng, 0.18, 0.35, 3),
        "Protein_Content": uniform(rng, 2.30, 2.95, 2),
        "Lactose_Content": uniform(rng, 3.50, 4.40, 2),
        "TPC": randint_rounded(rng, 2_000_100, 8_000_000, 100),
        "SCC": randint_rounded(rng, 300_100, 1_200_000, 100),
        "Color": rng.choice([0, 0, 1]),
        "Label": "Low",
    }

    if sample["Taste"] == sample["Odor"] == sample["Color"] == 1:
        sample[rng.choice(["Taste", "Odor", "Color"])] = 0

    return sample


def generate_dataset() -> list[dict[str, float | int | str]]:
    rng = random.Random(RANDOM_SEED)
    rows = []
    generators = {
        "High": high_sample,
        "Medium": medium_sample,
        "Low": low_sample,
    }

    for label in QUALITY_LABELS:
        for _ in range(ROWS_PER_CLASS):
            rows.append(generators[label](rng))

    rng.shuffle(rows)
    return rows


def validate_dataset(rows: list[dict[str, float | int | str]]) -> None:
    expected_columns = APPROVED_MODEL_FEATURES + ["Label"]
    if not rows:
        raise ValueError("Dataset is empty.")

    for row in rows:
        if list(row) != expected_columns:
            raise ValueError(f"Invalid columns: {list(row)}")
        if any(row[column] in ("", None) for column in expected_columns):
            raise ValueError(f"Missing value detected: {row}")
        if row["Label"] not in QUALITY_LABELS:
            raise ValueError(f"Invalid label: {row['Label']}")

    counts = Counter(row["Label"] for row in rows)
    expected_counts = {label: ROWS_PER_CLASS for label in QUALITY_LABELS}
    if dict(counts) != expected_counts:
        raise ValueError(f"Invalid class balance: {dict(counts)}")

    feature_rows = [tuple(row[feature] for feature in APPROVED_MODEL_FEATURES) for row in rows]
    duplicate_count = len(feature_rows) - len(set(feature_rows))
    if duplicate_count:
        raise ValueError(f"Duplicate feature rows detected: {duplicate_count}")

    for row in rows:
        if not 0.0 <= row["pH"] <= 14.0:
            raise ValueError(f"Impossible pH value: {row}")
        if row["Temperature"] < 0.0:
            raise ValueError(f"Impossible temperature value: {row}")
        if row["Fat_Content"] < 0.0:
            raise ValueError(f"Impossible fat value: {row}")
        if row["Titratable_Acidity"] < 0.0:
            raise ValueError(f"Impossible acidity value: {row}")
        if row["Protein_Content"] < 0.0:
            raise ValueError(f"Impossible protein value: {row}")
        if row["Lactose_Content"] < 0.0:
            raise ValueError(f"Impossible lactose value: {row}")
        if row["TPC"] < 0 or row["SCC"] < 0:
            raise ValueError(f"Impossible microbiological count: {row}")
        if row["Taste"] not in (0, 1) or row["Odor"] not in (0, 1) or row["Color"] not in (0, 1):
            raise ValueError(f"Invalid sensory encoding: {row}")


def write_dataset(rows: list[dict[str, float | int | str]]) -> None:
    DATASET_PATH.parent.mkdir(parents=True, exist_ok=True)
    with DATASET_PATH.open("w", newline="") as csv_file:
        writer = csv.DictWriter(csv_file, fieldnames=APPROVED_MODEL_FEATURES + ["Label"])
        writer.writeheader()
        writer.writerows(rows)


def write_metadata(rows: list[dict[str, float | int | str]]) -> None:
    metadata = {
        "dataset_version": DATASET_VERSION,
        "label_policy_version": LABEL_POLICY_VERSION,
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "random_seed": RANDOM_SEED,
        "row_count": len(rows),
        "rows_per_class": ROWS_PER_CLASS,
        "class_distribution": {
            label: Counter(row["Label"] for row in rows)[label] for label in QUALITY_LABELS
        },
        "features": APPROVED_MODEL_FEATURES,
        "target_column": "Label",
        "feature_units": FEATURE_UNITS,
        "sources": SOURCES,
        "generation_policy": {
            "High": "Within configured standards and normal sensory values.",
            "Medium": "Mild numeric deviations and occasional sensory defects.",
            "Low": "Clear numeric failures and at least one sensory defect.",
        },
        "excluded_model_inputs": {
            "SNF": "Excluded from the approved 11-feature model contract.",
            "Turbidity": "Excluded because it is not part of the approved thesis feature contract.",
        },
        "validation_checks": [
            "approved columns only",
            "no missing values",
            "valid labels only",
            "500 rows per class",
            "no duplicate feature rows",
            "no impossible numeric values",
            "sensory values encoded as 0 or 1",
        ],
    }
    METADATA_PATH.write_text(json.dumps(metadata, indent=2) + "\n")


def main() -> None:
    rows = generate_dataset()
    validate_dataset(rows)
    write_dataset(rows)
    write_metadata(rows)
    print(f"Wrote {len(rows)} rows to {DATASET_PATH.relative_to(ROOT)}")
    print(f"Wrote metadata to {METADATA_PATH.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
