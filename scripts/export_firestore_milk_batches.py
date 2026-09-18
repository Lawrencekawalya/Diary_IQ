"""Export legacy DairyIQ Firestore milk batch records to JSON."""

from __future__ import annotations

import argparse
import json
from pathlib import Path
from typing import Any

import firebase_admin
from firebase_admin import credentials, firestore


def firestore_value(value: Any) -> Any:
    if hasattr(value, "isoformat"):
        return value.isoformat()

    if isinstance(value, dict):
        return {key: firestore_value(item) for key, item in value.items()}

    if isinstance(value, list):
        return [firestore_value(item) for item in value]

    return value


def main() -> None:
    parser = argparse.ArgumentParser(description="Export Firestore milk_batches records to JSON.")
    parser.add_argument(
        "--credentials",
        default="firebase/firebase_key.json",
        help="Path to the Firebase service account JSON.",
    )
    parser.add_argument(
        "--collection",
        default="milk_batches",
        help="Firestore collection to export.",
    )
    parser.add_argument(
        "--output",
        default="data/firestore_milk_batches_export.json",
        help="Destination JSON path.",
    )
    args = parser.parse_args()

    if not firebase_admin._apps:
        firebase_admin.initialize_app(credentials.Certificate(args.credentials))

    db = firestore.client()
    records = []

    for document in db.collection(args.collection).stream():
        record = firestore_value(document.to_dict())
        record["document_id"] = document.id
        records.append(record)

    output = Path(args.output)
    output.parent.mkdir(parents=True, exist_ok=True)
    output.write_text(json.dumps({"milk_batches": records}, indent=2), encoding="utf-8")

    print(f"Exported {len(records)} Firestore records to {output}")


if __name__ == "__main__":
    main()
