import importlib.util
from pathlib import Path


def load_main_module():
    spec = importlib.util.spec_from_file_location("dairyiq_main_for_tests", Path("app/main.py"))
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module


class FakeDocument:
    id = "TESTDOC"


class FakeCollection:
    def __init__(self):
        self.saved = []

    def add(self, record):
        self.saved.append(record)
        return None, FakeDocument()


class FakeDB:
    def __init__(self):
        self.collection_obj = FakeCollection()

    def collection(self, name):
        assert name == "milk_batches"
        return self.collection_obj


def valid_route_form():
    return {
        "collection_center": "Center A",
        "contact": "0700000000",
        "district": "Kampala",
        "location": "Nakawa",
        "driver_name": "Driver One",
        "transport_details": "UAX 123A",
        "tested_by": "Chemist One",
        "liters_collected": "120.5",
        "ph": "6.70",
        "temperature": "4.0",
        "taste": "normal",
        "odor": "fresh",
        "fat": "3.8",
        "acidity": "0.15",
        "protein": "3.3",
        "lactose": "4.8",
        "tpc": "50000",
        "scc": "200000",
        "color": "normal",
    }


def test_predict_route_writes_record_with_test_double(monkeypatch):
    main = load_main_module()
    fake_db = FakeDB()
    monkeypatch.setattr(main, "db", fake_db)
    main.app.config.update(TESTING=True, WTF_CSRF_ENABLED=False)

    client = main.app.test_client()
    with client.session_transaction() as session:
        session["user"] = "tester@example.com"

    response = client.post("/predict", data=valid_route_form(), follow_redirects=False)

    assert response.status_code == 302
    assert response.headers["Location"].endswith("/result/TESTDOC")
    saved = fake_db.collection_obj.saved[0]
    assert saved["prediction"] == "High"
    assert saved["record_schema_version"] == "milk_batch_prediction_v1"
    assert "probabilities" in saved
    assert "password" not in saved
    assert "idToken" not in saved
