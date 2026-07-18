# Flask Backend Integration

Phase 7 switches backend prediction from the legacy nine-feature pickle to the
validated thesis-aligned Random Forest artifact.

## Artifact Loading

`app/main.py` loads the model through:

`model_artifact.load_model_bundle`

The artifact path is resolved from the project root:

`ml_model/artifacts/milk_quality_rf_v1.joblib`

Startup fails if the artifact does not match the approved feature order,
classes, or sensory encoding.

## Prediction Service

Prediction logic now lives in:

`prediction_service.py`

The service handles:

- server-side parsing and validation of all 11 approved inputs
- binary encoding for `Taste`, `Odor`, and `Color`
- DataFrame construction in the exact model feature order
- `predict()` inference
- `predict_proba()` probability extraction
- probability mapping using `model.classes_`
- confidence calculation
- standards observations separate from the ML prediction
- model metadata returned with every prediction

## Request Contract

The backend expects these form field names:

| Feature | Form field |
| --- | --- |
| `pH` | `ph` |
| `Temperature` | `temperature` |
| `Taste` | `taste` |
| `Odor` | `odor` |
| `Fat_Content` | `fat` |
| `Titratable_Acidity` | `acidity` |
| `Protein_Content` | `protein` |
| `Lactose_Content` | `lactose` |
| `TPC` | `tpc` |
| `SCC` | `scc` |
| `Color` | `color` |

Phase 8 updates the HTML form to submit the new sensory fields and remove `SNF`
from prediction input.

## Firestore Record Additions

New prediction records include:

- all 11 encoded model inputs
- original sensory selections
- encoded sensory values
- prediction label
- class probabilities
- confidence
- model version
- dataset version
- label-policy version
- feature order
- class list
- standards observations

Legacy history rendering still maps old `Moderate` records to `Medium`.
