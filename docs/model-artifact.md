# Model Artifact

Phase 6 creates a versioned Random Forest bundle for the approved DairyIQ
11-feature contract.

Build command:

```bash
python scripts/build_model_artifact.py
```

## Output Files

Artifact:

`ml_model/artifacts/milk_quality_rf_v1.joblib`

Sidecar metadata:

`ml_model/artifacts/milk_quality_rf_v1.metadata.json`

Artifact checksum:

`ml_model/artifacts/milk_quality_rf_v1.joblib.sha256`

## Bundle Contents

The `.joblib` file stores a dictionary with:

- `model`: fitted scikit-learn pipeline containing the Random Forest classifier
- `metadata`: artifact metadata required for compatibility validation

The metadata includes:

- artifact schema version
- model version
- ordered 11-feature list
- class list: `Low`, `Medium`, `High`
- sensory encoding map
- dataset version
- label-policy version
- Random Forest metrics from Phase 5
- Python, pandas, scikit-learn, joblib, and XGBoost versions
- training timestamp
- model payload SHA-256

The sidecar metadata also includes the final artifact file SHA-256.

## Compatibility Validation

`model_artifact.py` provides:

- `load_model_bundle(path)`
- `validate_model_bundle(bundle)`

The Flask app loads `ml_model/artifacts/milk_quality_rf_v1.joblib` through this
validator at startup. Startup fails if the artifact feature order, classes, or
sensory encoding do not match the approved contract.

Phase 7 will update the form and prediction route to send the approved
11-feature input frame into this artifact.
