# Training and Evaluation

Phase 5 is implemented by:

`scripts/train_evaluate.py`

Run from the repository root:

```bash
python scripts/train_evaluate.py
```

## Dataset

Input dataset:

`data/milk_quality_eas67_2023_synth_1500.csv`

The script validates that the dataset uses the approved 11-feature contract,
contains no missing values, contains no duplicate feature rows, and has 500
records for each class.

## Evaluation Protocol

The script uses:

- deterministic random seed: `42`
- stratified 80/20 train/test split
- 1,200 training rows
- 300 testing rows
- five-fold stratified cross-validation

## Metrics Produced

The script reports:

- accuracy
- balanced accuracy
- macro precision
- macro recall
- macro F1
- per-class precision, recall, F1, and support
- confusion matrix
- five-fold cross-validation scores
- learning-curve data
- Random Forest feature importance

## Output Files

Evaluation folder:

`reports/model-evaluation/`

Files:

- `metrics.json`
- `model_comparison.csv`
- `random_forest_confusion_matrix.csv`
- `random_forest_feature_importance.csv`
- `random_forest_learning_curve.csv`
- `random_forest_phase5_candidate.joblib`

The candidate model is for Phase 5 evidence only. The production model bundle
with metadata and compatibility checks is created in Phase 6.

## Results From Current Run

Random Forest test results:

- accuracy: `1.0000`
- balanced accuracy: `1.0000`
- macro precision: `1.0000`
- macro recall: `1.0000`
- macro F1: `1.0000`

Confusion matrix:

| Actual | Predicted Low | Predicted Medium | Predicted High |
| --- | ---: | ---: | ---: |
| Low | 100 | 0 | 0 |
| Medium | 0 | 100 | 0 |
| High | 0 | 0 | 100 |

The thesis-reported Random Forest accuracy of `99.5%` was not reproduced
exactly on this generated dataset and split. The current reproducible result is
`100.0%`. This should be reported honestly, with the note that the dataset is
synthetic and strongly separated by the generation policy.

## Comparative Models

The current environment evaluated:

- Random Forest
- SVM
- ANN using scikit-learn `MLPClassifier`

XGBoost is supported by the script as an optional comparison, but it was not
run because `xgboost` is not installed in the current environment and is not
listed in `requirements.txt`.
