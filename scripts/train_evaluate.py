"""Train and evaluate thesis-aligned DairyIQ models.

Run from the repository root:

    python scripts/train_evaluate.py

This phase writes evaluation outputs only. The production model bundle is
created in Phase 6.
"""

from __future__ import annotations

import json
import sys
from datetime import datetime, timezone
from pathlib import Path

import joblib
import pandas as pd
import sklearn
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import (
    accuracy_score,
    balanced_accuracy_score,
    classification_report,
    confusion_matrix,
    precision_recall_fscore_support,
)
from sklearn.model_selection import StratifiedKFold, cross_validate, learning_curve, train_test_split
from sklearn.neural_network import MLPClassifier
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import LabelEncoder
from sklearn.preprocessing import StandardScaler
from sklearn.svm import SVC

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT))

from model_contract import APPROVED_MODEL_FEATURES, QUALITY_LABELS

DATASET_PATH = ROOT / "data" / "milk_quality_eas67_2023_synth_1500.csv"
DATASET_METADATA_PATH = ROOT / "data" / "milk_quality_eas67_2023_synth_1500.metadata.json"
REPORT_DIR = ROOT / "reports" / "model-evaluation"
MODEL_OUTPUT_PATH = REPORT_DIR / "random_forest_phase5_candidate.joblib"
METRICS_PATH = REPORT_DIR / "metrics.json"
CONFUSION_MATRIX_PATH = REPORT_DIR / "random_forest_confusion_matrix.csv"
FEATURE_IMPORTANCE_PATH = REPORT_DIR / "random_forest_feature_importance.csv"
LEARNING_CURVE_PATH = REPORT_DIR / "random_forest_learning_curve.csv"
COMPARISON_PATH = REPORT_DIR / "model_comparison.csv"

RANDOM_STATE = 42
TEST_SIZE = 0.2
CV_SPLITS = 5


def load_dataset() -> tuple[pd.DataFrame, dict]:
    df = pd.read_csv(DATASET_PATH)
    metadata = json.loads(DATASET_METADATA_PATH.read_text())
    expected_columns = APPROVED_MODEL_FEATURES + ["Label"]

    if list(df.columns) != expected_columns:
        raise ValueError(f"Dataset columns do not match contract: {list(df.columns)}")
    if df.isna().sum().sum():
        raise ValueError("Dataset contains missing values.")
    class_counts = df["Label"].value_counts().reindex(QUALITY_LABELS).to_dict()
    if class_counts != {label: 500 for label in QUALITY_LABELS}:
        raise ValueError(f"Dataset class balance is invalid: {class_counts}")
    if int(df.duplicated(subset=APPROVED_MODEL_FEATURES).sum()):
        raise ValueError("Dataset contains duplicate feature rows.")

    return df, metadata


def build_models() -> dict[str, object]:
    models: dict[str, object] = {
        "Random Forest": RandomForestClassifier(
            n_estimators=300,
            max_depth=None,
            min_samples_split=2,
            min_samples_leaf=1,
            random_state=RANDOM_STATE,
            class_weight="balanced",
            n_jobs=1,
        ),
        "SVM": Pipeline(
            steps=[
                ("scaler", StandardScaler()),
                ("classifier", SVC(kernel="rbf", C=10, gamma="scale", probability=True, random_state=RANDOM_STATE)),
            ]
        ),
        "ANN": Pipeline(
            steps=[
                ("scaler", StandardScaler()),
                (
                    "classifier",
                    MLPClassifier(
                        hidden_layer_sizes=(64, 32),
                        activation="relu",
                        solver="adam",
                        alpha=0.0001,
                        max_iter=1000,
                        early_stopping=True,
                        random_state=RANDOM_STATE,
                    ),
                ),
            ]
        ),
    }

    try:
        from xgboost import XGBClassifier  # type: ignore

        models["XGBoost"] = XGBClassifier(
            n_estimators=300,
            learning_rate=0.05,
            max_depth=6,
            random_state=RANDOM_STATE,
            subsample=0.8,
            colsample_bytree=0.8,
            eval_metric="mlogloss",
        )
    except Exception:
        pass

    return models


def score_predictions(y_true: pd.Series, y_pred: pd.Series | list[str]) -> dict[str, float]:
    precision, recall, f1, _ = precision_recall_fscore_support(
        y_true,
        y_pred,
        labels=QUALITY_LABELS,
        average="macro",
        zero_division=0,
    )
    return {
        "accuracy": accuracy_score(y_true, y_pred),
        "balanced_accuracy": balanced_accuracy_score(y_true, y_pred),
        "macro_precision": precision,
        "macro_recall": recall,
        "macro_f1": f1,
    }


def evaluate_model(name: str, model: object, x_train, x_test, y_train, y_test, x_all, y_all) -> dict:
    target_encoder = None
    y_train_fit = y_train
    y_all_fit = y_all

    if name in {"ANN", "XGBoost"}:
        target_encoder = LabelEncoder()
        target_encoder.fit(QUALITY_LABELS)
        y_train_fit = target_encoder.transform(y_train)
        y_all_fit = target_encoder.transform(y_all)

    model.fit(x_train, y_train_fit)
    y_pred = model.predict(x_test)
    if target_encoder is not None:
        y_pred = target_encoder.inverse_transform(y_pred.astype(int))

    scores = score_predictions(y_test, y_pred)

    cv = StratifiedKFold(n_splits=CV_SPLITS, shuffle=True, random_state=RANDOM_STATE)
    cv_scores = cross_validate(
        model,
        x_all,
        y_all_fit,
        cv=cv,
        scoring={
            "accuracy": "accuracy",
            "balanced_accuracy": "balanced_accuracy",
            "macro_f1": "f1_macro",
        },
        n_jobs=1,
    )

    return {
        "model": name,
        "test_metrics": scores,
        "classification_report": classification_report(
            y_test,
            y_pred,
            labels=QUALITY_LABELS,
            output_dict=True,
            zero_division=0,
        ),
        "cross_validation": {
            "folds": CV_SPLITS,
            "accuracy": cv_scores["test_accuracy"].tolist(),
            "balanced_accuracy": cv_scores["test_balanced_accuracy"].tolist(),
            "macro_f1": cv_scores["test_macro_f1"].tolist(),
            "accuracy_mean": float(cv_scores["test_accuracy"].mean()),
            "balanced_accuracy_mean": float(cv_scores["test_balanced_accuracy"].mean()),
            "macro_f1_mean": float(cv_scores["test_macro_f1"].mean()),
        },
    }


def write_random_forest_outputs(model, x_train, y_train, x_test, y_test, x_all, y_all) -> None:
    y_pred = model.predict(x_test)

    matrix = pd.DataFrame(
        confusion_matrix(y_test, y_pred, labels=QUALITY_LABELS),
        index=[f"actual_{label}" for label in QUALITY_LABELS],
        columns=[f"predicted_{label}" for label in QUALITY_LABELS],
    )
    matrix.to_csv(CONFUSION_MATRIX_PATH)

    importance = pd.DataFrame(
        {
            "feature": APPROVED_MODEL_FEATURES,
            "importance": model.feature_importances_,
        }
    ).sort_values("importance", ascending=False)
    importance.to_csv(FEATURE_IMPORTANCE_PATH, index=False)

    cv = StratifiedKFold(n_splits=CV_SPLITS, shuffle=True, random_state=RANDOM_STATE)
    train_sizes, train_scores, validation_scores = learning_curve(
        model,
        x_all,
        y_all,
        cv=cv,
        train_sizes=[0.2, 0.4, 0.6, 0.8, 1.0],
        scoring="accuracy",
        n_jobs=1,
    )
    curve = pd.DataFrame(
        {
            "train_size": train_sizes,
            "train_accuracy_mean": train_scores.mean(axis=1),
            "train_accuracy_std": train_scores.std(axis=1),
            "validation_accuracy_mean": validation_scores.mean(axis=1),
            "validation_accuracy_std": validation_scores.std(axis=1),
        }
    )
    curve.to_csv(LEARNING_CURVE_PATH, index=False)

    joblib.dump(model, MODEL_OUTPUT_PATH)


def main() -> None:
    REPORT_DIR.mkdir(parents=True, exist_ok=True)

    df, dataset_metadata = load_dataset()
    x = df[APPROVED_MODEL_FEATURES]
    y = df["Label"]
    x_train, x_test, y_train, y_test = train_test_split(
        x,
        y,
        test_size=TEST_SIZE,
        random_state=RANDOM_STATE,
        stratify=y,
    )

    models = build_models()
    results = []
    fitted_random_forest = None

    for name, model in models.items():
        result = evaluate_model(name, model, x_train, x_test, y_train, y_test, x, y)
        results.append(result)
        if name == "Random Forest":
            fitted_random_forest = model

    if fitted_random_forest is None:
        raise RuntimeError("Random Forest model was not trained.")

    write_random_forest_outputs(fitted_random_forest, x_train, y_train, x_test, y_test, x, y)

    comparison = pd.DataFrame(
        [
            {
                "model": item["model"],
                **item["test_metrics"],
                "cv_accuracy_mean": item["cross_validation"]["accuracy_mean"],
                "cv_balanced_accuracy_mean": item["cross_validation"]["balanced_accuracy_mean"],
                "cv_macro_f1_mean": item["cross_validation"]["macro_f1_mean"],
            }
            for item in results
        ]
    )
    comparison.to_csv(COMPARISON_PATH, index=False)

    rf_metrics = next(item for item in results if item["model"] == "Random Forest")
    metrics = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "dataset_path": str(DATASET_PATH.relative_to(ROOT)),
        "dataset_version": dataset_metadata["dataset_version"],
        "label_policy_version": dataset_metadata["label_policy_version"],
        "feature_order": APPROVED_MODEL_FEATURES,
        "classes": QUALITY_LABELS,
        "random_state": RANDOM_STATE,
        "split": {
            "strategy": "stratified",
            "test_size": TEST_SIZE,
            "train_rows": int(len(x_train)),
            "test_rows": int(len(x_test)),
        },
        "dependencies": {
            "python": sys.version.split()[0],
            "pandas": pd.__version__,
            "scikit_learn": sklearn.__version__,
        },
        "models_evaluated": [item["model"] for item in results],
        "xgboost_status": "evaluated" if any(item["model"] == "XGBoost" for item in results) else "not installed; optional comparison skipped",
        "random_forest": rf_metrics,
        "all_models": results,
        "thesis_99_5_accuracy_check": {
            "reported_accuracy": 0.995,
            "reproduced_accuracy": rf_metrics["test_metrics"]["accuracy"],
            "matches_reported_accuracy": abs(rf_metrics["test_metrics"]["accuracy"] - 0.995) < 1e-12,
        },
        "outputs": {
            "comparison": str(COMPARISON_PATH.relative_to(ROOT)),
            "confusion_matrix": str(CONFUSION_MATRIX_PATH.relative_to(ROOT)),
            "feature_importance": str(FEATURE_IMPORTANCE_PATH.relative_to(ROOT)),
            "learning_curve": str(LEARNING_CURVE_PATH.relative_to(ROOT)),
            "phase5_candidate_model": str(MODEL_OUTPUT_PATH.relative_to(ROOT)),
        },
    }
    METRICS_PATH.write_text(json.dumps(metrics, indent=2) + "\n")

    print("Phase 5 training and evaluation complete.")
    print(f"Train rows: {len(x_train)}")
    print(f"Test rows: {len(x_test)}")
    print(f"Random Forest accuracy: {rf_metrics['test_metrics']['accuracy']:.4f}")
    print(f"Random Forest macro F1: {rf_metrics['test_metrics']['macro_f1']:.4f}")
    print(f"Wrote metrics to {METRICS_PATH.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
