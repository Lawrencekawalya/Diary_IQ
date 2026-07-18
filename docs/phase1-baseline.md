# Phase 1 Baseline and Cleanup Record

Date: 2026-07-18  
Branch: `align-approved-thesis`  
Baseline commit before Phase 1 work: `231a604 fix: ensure proper formatting in .env, .gitignore, and requirements.txt; update Firebase credentials path in main.py; add debug logging in login function`

## Current Active Model

Active Flask model path before Phase 1 cleanup:

`ml_model/dairy_model_4class.pkl`

SHA-256:

`42a86b97fd60ac2729235af56b5b32cf250384a5a07308a97ea70acea342e80a`

Model type:

`sklearn.ensemble._forest.RandomForestClassifier`

Random Forest parameters captured from the artifact:

- `n_estimators`: `200`
- `max_depth`: `None`
- `class_weight`: `balanced`
- `random_state`: `42`

## Current Model Feature Contract

The current artifact uses nine features:

1. `pH`
2. `Temperature`
3. `Fat_Content`
4. `SNF`
5. `Titratable_Acidity`
6. `Protein_Content`
7. `Lactose_Content`
8. `TPC`
9. `SCC`

This does not match the approved thesis contract. The approved rebuild must use
the 11-feature contract and must remove `SNF` from model input unless it is kept
only as non-model metadata.

## Current Classes

The current artifact exposes these classes:

1. `High`
2. `Low`
3. `Moderate`

This does not match the approved thesis labels. The approved rebuild must use
`Low`, `Medium`, and `High`.

## Baseline Prediction Behavior

The following samples were run against `ml_model/dairy_model_4class.pkl`.

| Sample | Prediction | High | Low | Moderate |
| --- | --- | ---: | ---: | ---: |
| baseline_high_like | High | 0.6250 | 0.0000 | 0.3750 |
| baseline_low_like | Low | 0.0000 | 1.0000 | 0.0000 |
| baseline_medium_like | Moderate | 0.0500 | 0.1400 | 0.8100 |

The baseline confirms that current behavior still includes `Moderate`, so it is
not yet thesis-aligned.

## Current Dataset

Current training dataset path:

`ml_model/retrain_ML/milk_quality_dataset.csv`

Shape from file inspection:

- `1000` data rows
- `1` header row
- `9` model feature columns
- target column: `Label`
- extra column: `All_Normal`

Current dataset columns:

`pH`, `Temperature`, `Fat_Content`, `SNF`, `Titratable_Acidity`,
`Protein_Content`, `Lactose_Content`, `TPC`, `SCC`, `Label`, `All_Normal`

This does not match the approved thesis requirement of 1,500 synthesized rows,
500 per class, using exactly 11 approved model features.

## Duplicate Model Artifacts

| Path | SHA-256 | Size |
| --- | --- | ---: |
| `ml_model/dairy_model.pkl` | `337c5c5c44c721adc6527d4572c386a63a7964d2fbe3db91058c437b4becce90` | `64297` bytes |
| `ml_model/dairy_model_3class.pkl` | `ad250932e25cf7f2c4dda3d597a33df56901ffb4364d8a3ee7a04bef0adbb577` | `83761` bytes |
| `ml_model/dairy_model_4class.pkl` | `42a86b97fd60ac2729235af56b5b32cf250384a5a07308a97ea70acea342e80a` | `1762033` bytes |
| `ml_model/retrain_ML/dairy_model_3class.pkl` | `42a86b97fd60ac2729235af56b5b32cf250384a5a07308a97ea70acea342e80a` | `1762033` bytes |
| `ml_model/retrain_ML/dairy_model_4class.pkl` | `42a86b97fd60ac2729235af56b5b32cf250384a5a07308a97ea70acea342e80a` | `1762033` bytes |
| `notebooks/ml_model/dairy_model_3class.pkl` | `ad250932e25cf7f2c4dda3d597a33df56901ffb4364d8a3ee7a04bef0adbb577` | not under active `ml_model` tree |

The active nine-feature model was copied to:

`ml_model/archive/dairy_model_4class_legacy_9feature_2026-07-18_sha42a86b97.pkl`

The Flask app now loads the same legacy baseline artifact from a clearer
temporary path:

`ml_model/dairy_model_legacy_9feature.pkl`

No active model artifact was deleted during Phase 1.

## Cleanup Completed

- Replaced tracked `.env` values with placeholders.
- Added `.env.example`.
- Added `.env`, secret variants, logs, Python cache, and generated model output
  directories to `.gitignore`.
- Removed hard-coded Firebase API key, email, and password from
  `app/test_firebase_login.py`.
- Removed full Firebase login response printing from `app/main.py`.
- Changed `/debug/firebase` so it no longer returns the Firebase API key.
- Updated Flask to load `ml_model/dairy_model_legacy_9feature.pkl` instead of
  the misleading `ml_model/dairy_model_4class.pkl` path.

## Credential Rotation Decision

The Firebase API key, Firebase test user password, and Flask secret that were
previously committed were identified during Phase 1 cleanup. Rotation is
intentionally deferred by the project owner for now and will be handled in a
future security pass.

For the current implementation workflow, Phase 1 is considered complete. The
repository no longer contains the hard-coded Firebase test credentials in the
login test script, and the tracked `.env` file now uses placeholders.
