"""Prediction service for the approved DairyIQ model contract."""

from __future__ import annotations

from typing import Any, Mapping

import pandas as pd

from model_contract import (
    APPROVED_MODEL_FEATURES,
    QUALITY_LABELS,
    encode_sensory_value,
    normalize_quality_label,
)

NUMERIC_FORM_FIELDS = {
    "pH": ("ph", "pH Level"),
    "Temperature": ("temperature", "Temperature"),
    "Fat_Content": ("fat", "Fat Content"),
    "Titratable_Acidity": ("acidity", "Titratable Acidity"),
    "Protein_Content": ("protein", "Protein Content"),
    "Lactose_Content": ("lactose", "Lactose Content"),
    "TPC": ("tpc", "Total Plate Count"),
    "SCC": ("scc", "Somatic Cell Count"),
}

SENSORY_FORM_FIELDS = {
    "Taste": ("taste", "Taste"),
    "Odor": ("odor", "Odor"),
    "Color": ("color", "Color"),
}


def parse_float(form: Mapping[str, Any], field_name: str, label: str) -> float:
    value = form.get(field_name)
    if value in (None, ""):
        raise ValueError(f"{label} is required.")
    try:
        return float(value)
    except ValueError as exc:
        raise ValueError(f"Invalid entry for {label}. Please enter a numeric value.") from exc


def parse_model_inputs(form: Mapping[str, Any]) -> tuple[dict[str, float | int], dict[str, str], dict[str, int]]:
    numeric_values = {
        feature: parse_float(form, field_name, label)
        for feature, (field_name, label) in NUMERIC_FORM_FIELDS.items()
    }

    sensory_labels = {}
    encoded_sensory_values = {}
    for feature, (field_name, label) in SENSORY_FORM_FIELDS.items():
        value = form.get(field_name)
        if value in (None, ""):
            raise ValueError(f"{label} is required.")
        sensory_labels[feature] = str(value)
        encoded_sensory_values[feature] = encode_sensory_value(feature, value)

    raw = {
        "pH": numeric_values["pH"],
        "Temperature": numeric_values["Temperature"],
        "Taste": encoded_sensory_values["Taste"],
        "Odor": encoded_sensory_values["Odor"],
        "Fat_Content": numeric_values["Fat_Content"],
        "Titratable_Acidity": numeric_values["Titratable_Acidity"],
        "Protein_Content": numeric_values["Protein_Content"],
        "Lactose_Content": numeric_values["Lactose_Content"],
        "TPC": numeric_values["TPC"],
        "SCC": numeric_values["SCC"],
        "Color": encoded_sensory_values["Color"],
    }
    return raw, sensory_labels, encoded_sensory_values


def build_feature_frame(raw: Mapping[str, float | int]) -> pd.DataFrame:
    missing = [feature for feature in APPROVED_MODEL_FEATURES if feature not in raw]
    if missing:
        raise ValueError(f"Missing model features: {', '.join(missing)}")
    return pd.DataFrame([{feature: raw[feature] for feature in APPROVED_MODEL_FEATURES}])


def build_standards_observations(
    raw: Mapping[str, float | int],
    standards: Mapping[str, Mapping[str, Any]],
) -> tuple[list[str], list[str]]:
    colors = []
    observations = []

    for feature in APPROVED_MODEL_FEATURES:
        value = raw[feature]
        rule = standards.get(feature)
        if not rule:
            colors.append("#bdc3c7")
            continue

        low, high = rule.get("Min"), rule.get("Max")
        in_range = True

        if low is not None and value < low:
            in_range = False
            observations.append(f"Warning: {feature}: below normal ({value}) - {rule['Remarks']}")

        if high is not None and value > high:
            in_range = False
            observations.append(f"Warning: {feature}: above normal ({value}) - {rule['Remarks']}")

        colors.append("#2ecc71" if in_range else "#e67e22")

    if not observations:
        observations.append("Milk meets the configured standards thresholds.")
        observations.append("Maintain current handling procedures.")

    return colors, observations


def predict_milk_quality(
    form: Mapping[str, Any],
    model: Any,
    metadata: Mapping[str, Any],
    standards: Mapping[str, Mapping[str, Any]],
) -> dict[str, Any]:
    raw, sensory_labels, encoded_sensory_values = parse_model_inputs(form)
    feature_frame = build_feature_frame(raw)

    prediction = normalize_quality_label(model.predict(feature_frame)[0])
    probabilities = {}
    confidence = None
    if hasattr(model, "predict_proba"):
        probability_values = model.predict_proba(feature_frame)[0]
        model_classes = [normalize_quality_label(label) for label in model.classes_]
        probabilities = {
            class_label: float(probability)
            for class_label, probability in zip(model_classes, probability_values)
        }
        probabilities = {
            class_label: probabilities.get(class_label, 0.0)
            for class_label in QUALITY_LABELS
        }
        confidence = max(probabilities.values()) if probabilities else None

    colors, standards_observations = build_standards_observations(raw, standards)

    return {
        "raw": raw,
        "sensory_inputs": sensory_labels,
        "encoded_sensory_values": encoded_sensory_values,
        "prediction": prediction,
        "confidence": confidence,
        "probabilities": probabilities,
        "colors": colors,
        "standards_observations": standards_observations,
        "model_metadata": {
            "model_version": metadata["model_version"],
            "dataset_version": metadata["dataset_version"],
            "label_policy_version": metadata["label_policy_version"],
            "feature_order": metadata["feature_order"],
            "classes": metadata["classes"],
        },
    }
