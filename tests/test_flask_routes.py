import importlib.util
from pathlib import Path

import firebase_admin


def load_main_module():
    try:
        firebase_admin.delete_app(firebase_admin.get_app())
    except ValueError:
        pass

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


class FakeHistoryDocument:
    def __init__(self, record, document_id):
        self.record = record
        self.id = document_id

    def to_dict(self):
        return self.record


class FakeHistoryCollection:
    def __init__(self, records):
        self.records = records
        self.order_direction = None

    def order_by(self, field, direction=None):
        assert field == "created_at"
        self.order_direction = direction
        return self

    def stream(self):
        return [
            FakeHistoryDocument(record, f"DOC-{index}")
            for index, record in enumerate(self.records, start=1)
        ]


class FakeDB:
    def __init__(self):
        self.collection_obj = FakeCollection()

    def collection(self, name):
        assert name == "milk_batches"
        return self.collection_obj


class FakeHistoryDB:
    def __init__(self, records):
        self.collection_obj = FakeHistoryCollection(records)

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
    assert saved["ml_prediction"] == "High"
    assert saved["standards_quality_gate"]["applied"] is False
    assert saved["record_schema_version"] == "milk_batch_prediction_v1"
    assert "probabilities" in saved
    assert "password" not in saved
    assert "idToken" not in saved


def test_history_route_uses_latest_table_order_and_chronological_chart(monkeypatch):
    main = load_main_module()
    records = [
        {
            "Batch Number": "LATEST",
            "Time of Collection": "2026-07-18 12:00:00",
            "prediction": "High",
            "created_at": "latest",
        },
        {
            "Batch Number": "OLDER",
            "Time of Collection": "2026-07-18 11:00:00",
            "prediction": "Low",
            "created_at": "older",
        },
    ]
    fake_db = FakeHistoryDB(records)
    captured = {}

    def fake_render_template(template, **context):
        captured["template"] = template
        captured.update(context)
        return "OK"

    monkeypatch.setattr(main, "db", fake_db)
    monkeypatch.setattr(main, "render_template", fake_render_template)
    main.app.config.update(TESTING=True)

    client = main.app.test_client()
    with client.session_transaction() as session:
        session["user"] = "tester@example.com"

    response = client.get("/history")

    assert response.status_code == 200
    assert fake_db.collection_obj.order_direction == main.firestore.Query.DESCENDING
    assert captured["template"] == "history.html"
    assert captured["history_data"][0]["Batch Number"] == "LATEST"
    assert captured["history_data"][0]["Document ID"] == "DOC-1"
    assert captured["chart_data"][0]["date"] == "2026-07-18 11:00:00"
    assert captured["chart_data"][-1]["date"] == "2026-07-18 12:00:00"
    assert captured["total_samples"] == 2
    assert captured["quality_insights"]["High"]["count"] == 1
    assert captured["quality_insights"]["High"]["percentage"] == 50
