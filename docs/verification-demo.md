# Verification and Demo

Phase 12 verifies that the thesis-aligned DairyIQ implementation can be rebuilt,
loaded, tested, and demonstrated with the approved 11-feature Random Forest
contract.

## Automated Verification

The following checks were completed from the repository root on July 18, 2026.

### Dataset Regeneration

Command:

```bash
venv/bin/python scripts/generate_dataset.py
```

Result:

- Generated `data/milk_quality_eas67_2023_synth_1500.csv`.
- Generated 1,500 synthesized samples.
- Class balance remains 500 `Low`, 500 `Medium`, and 500 `High` records.
- Dataset metadata was regenerated at
  `data/milk_quality_eas67_2023_synth_1500.metadata.json`.

### Training and Evaluation

Command:

```bash
venv/bin/python scripts/train_evaluate.py
```

Result:

- Stratified split: 1,200 training rows and 300 testing rows.
- Random Forest test accuracy: `1.0000`.
- Random Forest macro F1: `1.0000`.
- SVM, ANN, and XGBoost comparison results were regenerated.
- Evaluation artifacts were written under `reports/model-evaluation/`.

### Model Artifact Build

Command:

```bash
venv/bin/python scripts/build_model_artifact.py
```

Result:

- Generated `ml_model/artifacts/milk_quality_rf_v1.joblib`.
- Generated `ml_model/artifacts/milk_quality_rf_v1.metadata.json`.
- Generated `ml_model/artifacts/milk_quality_rf_v1.joblib.sha256`.
- Artifact SHA-256:
  `6903b37a1c3a1c0806bc44ae24a0a3a1cfd5ce3a67b6dd56461aa7dc5c4dd8c5`.

### Artifact Load Check

Result:

- Artifact loaded as `milk_quality_rf_v1`.
- Compatibility warning count: `0`.
- Feature order matches the approved 11-feature contract.
- Classes match `Low`, `Medium`, and `High`.

### Prediction Samples

The prediction service was tested with one sample for each thesis label:

```text
High: predicted=High confidence=0.7633
Medium: predicted=Medium confidence=0.6167
Low: predicted=Low confidence=1.0000
```

An unsafe out-of-range sample was also checked to confirm the standards safety
gate prevents contradictory `High` results:

```text
ml_prediction: High
final_prediction: Low
gate_applied: True
max_allowed_quality: Low
critical_features: pH, Taste, Fat_Content, Titratable_Acidity, Protein_Content, Lactose_Content, Color
```

### Test Suite

Command:

```bash
venv/bin/python -m pytest tests
```

Result:

```text
23 passed
```

The test suite covers the model contract, dataset generation, standards
configuration, model artifact validation, prediction service behavior, Flask
route behavior, and Firestore record shape using test doubles.

### Static Checks

Commands:

```bash
venv/bin/python -m py_compile app/main.py model_contract.py prediction_service.py database_records.py model_artifact.py scripts/generate_dataset.py scripts/train_evaluate.py scripts/build_model_artifact.py
git diff --check
```

Result:

- Python byte-compilation passed.
- Git whitespace check passed.

## Flask Startup Check

Command:

```bash
timeout 5 venv/bin/python app/main.py
```

Result:

- Flask configuration loaded.
- Firebase API key was detected.
- Model artifact loaded before server startup.
- The sandbox blocked local port binding with `PermissionError: [Errno 1]
  Operation not permitted`.

This is an execution-environment limitation, not an application error. The live
browser demo must be completed from the user's normal desktop terminal.

## Manual Browser Demo Steps

Run the application from the project root:

```bash
cd /home/kawaly/Projects/Laravel_APPs/Diary_IQ
source venv/bin/activate
python app/main.py
```

Then verify the following in the browser:

1. Login opens successfully using the configured Firebase test account.
2. The prediction form shows exactly the 11 approved inputs.
3. `SNF` and `Turbidity` are not shown as model inputs.
4. A `High` sample submits and displays a `High` prediction.
5. A `Medium` sample submits and displays a `Medium` prediction.
6. A `Low` sample submits and displays a `Low` prediction.
7. The result page displays all 11 submitted inputs.
8. The result page displays confidence, class probabilities, model version,
   dataset version, and label-policy version.
9. The standards observations are shown separately from the ML prediction.
10. The history page loads saved records without breaking on legacy records.
11. Charts show only `Low`, `Medium`, and `High`.
12. Export output includes all 11 inputs and model metadata.

## Screenshot Checklist

Capture screenshots for the final thesis/demo evidence:

1. Login page.
2. Prediction form with the 11 approved features.
3. `High` prediction result.
4. `Medium` prediction result.
5. `Low` prediction result.
6. History page showing saved prediction records.
7. Charts using `Low`, `Medium`, and `High`.
8. Export/download evidence if required by the report.
