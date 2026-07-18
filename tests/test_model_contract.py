import pytest

from model_contract import (
    APPROVED_MODEL_FEATURES,
    QUALITY_LABELS,
    encode_sensory_value,
    normalize_quality_label,
)


def test_approved_contract_has_exactly_11_features():
    assert APPROVED_MODEL_FEATURES == [
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


def test_quality_labels_are_approved_labels():
    assert QUALITY_LABELS == ["Low", "Medium", "High"]
    assert normalize_quality_label("Moderate") == "Medium"


def test_sensory_encoding_contract():
    assert encode_sensory_value("Taste", "normal") == 1
    assert encode_sensory_value("Taste", "off-taste") == 0
    assert encode_sensory_value("Odor", "fresh") == 1
    assert encode_sensory_value("Odor", "objectionable") == 0
    assert encode_sensory_value("Color", "creamy-white") == 1
    assert encode_sensory_value("Color", "abnormal") == 0


def test_invalid_sensory_value_is_rejected():
    with pytest.raises(ValueError):
        encode_sensory_value("Taste", "unsafe")
