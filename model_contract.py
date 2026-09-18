"""Approved DairyIQ model contract.

This module is the single source of truth for the thesis-aligned feature list,
quality labels, and sensory encoding rules. The legacy model still uses the
nine-feature list until the retraining phase replaces the artifact.
"""

APPROVED_MODEL_FEATURES = [
    "pH",
    "Temperature",
    "Taste",
    "Odor",
    "Fat_Content",
    "Titratable_Acidity",
    "Protein_Content",
    "Lactose_Content",
    "TPC",
    "SCC",
    "Color",
]

LEGACY_MODEL_FEATURES = [
    "pH",
    "Temperature",
    "Fat_Content",
    "SNF",
    "Titratable_Acidity",
    "Protein_Content",
    "Lactose_Content",
    "TPC",
    "SCC",
]

QUALITY_LABELS = ["Low", "Medium", "High"]

LEGACY_LABEL_MAP = {
    "Moderate": "Medium",
}

QUALITY_MAP = {
    "Low": 0,
    "Medium": 1,
    "High": 2,
}

SENSORY_ENCODING = {
    "Taste": {
        "normal": 1,
        "acceptable": 1,
        "abnormal": 0,
        "off": 0,
        "off-taste": 0,
    },
    "Odor": {
        "fresh": 1,
        "normal": 1,
        "abnormal": 0,
        "objectionable": 0,
    },
    "Color": {
        "normal": 1,
        "creamy-white": 1,
        "white": 1,
        "abnormal": 0,
    },
}

SENSORY_DISPLAY_OPTIONS = {
    "Taste": [
        ("normal", "Normal / acceptable"),
        ("abnormal", "Abnormal / off-taste"),
    ],
    "Odor": [
        ("fresh", "Fresh / normal"),
        ("abnormal", "Abnormal / objectionable"),
    ],
    "Color": [
        ("normal", "Normal / creamy-white"),
        ("abnormal", "Abnormal appearance"),
    ],
}


def normalize_quality_label(label):
    """Return the approved public quality label for legacy or current labels."""
    return LEGACY_LABEL_MAP.get(label, label)


def encode_sensory_value(feature, value):
    """Encode a sensory form value using the approved binary sensory contract."""
    if value is None:
        raise ValueError(f"{feature} is required.")

    normalized = str(value).strip().lower()
    encoding = SENSORY_ENCODING.get(feature)
    if not encoding or normalized not in encoding:
        allowed = ", ".join(sorted(encoding or []))
        raise ValueError(f"Invalid {feature}. Allowed values: {allowed}.")

    return encoding[normalized]
