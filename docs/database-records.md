# Database Records

Phase 9 formalizes the Firestore `milk_batches` record shape.

## Record Builder

Record construction is centralized in:

`database_records.py`

The prediction route uses:

- `build_batch_info`
- `build_prediction_record`
- `history_row_from_record`
- `chart_point_from_record`

This keeps Firestore writes and legacy reads consistent.

## New Prediction Record Shape

Each new Firestore record includes:

- batch details
- all 11 approved measurements
- original sensory labels
- encoded sensory values
- prediction label
- class probabilities
- confidence
- model metadata
- standards observations
- Firestore server timestamp
- record schema version

The record schema version is:

`milk_batch_prediction_v1`

## Model Metadata

Stored metadata includes:

- model version
- dataset version
- label-policy version
- feature order
- class list

## Legacy Compatibility

Older records may be missing sensory fields, confidence, probabilities, or
model metadata. The history helpers convert those missing fields to blank
display values.

Older `Moderate` predictions are normalized to `Medium` for charts and tables.

## Secret Handling

The database layer refuses to store known auth/secret field names such as:

- `password`
- `idToken`
- `refreshToken`
- `firebase_api_key`
- `FIREBASE_API_KEY`

Firebase Authentication responses remain in the login flow only and are not
written into `milk_batches`.
