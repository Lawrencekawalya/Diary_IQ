import json
from pathlib import Path

from model_contract import APPROVED_MODEL_FEATURES


def load_standards():
    standards = json.loads(Path("config/standards.json").read_text())
    return {item["Parameter"]: item for item in standards}


def test_standards_match_approved_feature_contract():
    assert list(load_standards()) == APPROVED_MODEL_FEATURES


def test_eas67_thresholds_are_configured():
    standards = load_standards()

    assert standards["pH"]["Min"] == 6.6
    assert standards["pH"]["Max"] == 6.8
    assert standards["Fat_Content"]["Min"] == 3.25
    assert standards["Titratable_Acidity"]["Max"] == 0.17
    assert standards["TPC"]["Max"] == 2_000_000
    assert standards["SCC"]["Max"] == 300_000


def test_sensory_standards_are_binary_normal_rules():
    standards = load_standards()

    for feature in ["Taste", "Odor", "Color"]:
        assert standards[feature]["Type"] == "sensory"
        assert standards[feature]["Min"] == 1
        assert standards[feature]["Max"] == 1


def test_excluded_inputs_are_not_active_standards():
    standards = load_standards()

    assert "SNF" not in standards
    assert "Turbidity" not in standards
