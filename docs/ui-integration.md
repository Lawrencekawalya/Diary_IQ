# User Interface Integration

Phase 8 updates the active Flask templates to match the approved 11-feature
model contract.

## Prediction Form

Active template:

`app/templates/index.html`

The form now submits:

- `ph`
- `temperature`
- `taste`
- `odor`
- `fat`
- `acidity`
- `protein`
- `lactose`
- `tpc`
- `scc`
- `color`

`SNF` has been removed from the prediction form because it is not part of the
approved model input contract.

`Taste`, `Odor`, and `Color` are controlled select fields, not free-text fields.
Their values match the backend sensory encoding contract.

## Result Page

Active template:

`app/templates/result.html`

The result page displays:

- all 11 model inputs
- standards observations
- confidence
- class probabilities
- model version
- dataset version
- label-policy version
- batch information

Charts use the approved labels:

- `Low`
- `Medium`
- `High`

## History Page

Active template:

`app/templates/history.html`

The history table now includes the 11 model inputs plus prediction confidence,
model version, and dataset version. Legacy records with missing sensory fields
or missing model metadata render with blank values instead of failing.
