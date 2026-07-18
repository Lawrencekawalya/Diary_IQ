# DairyIQ Final Implementation Plan

## Objective

Align the current DairyIQ Flask system with the approved thesis:

`FINAL THESIS V2.docx`

The final system will be a web-based DairyIQ prototype that uses a Random
Forest model to classify raw cow milk into:

- `Low`
- `Medium`
- `High`

The model will use a standards-bounded synthesized dataset based primarily on
`US EAS 67:2023`, supported by Codex and FAO references where the standard does
not provide direct thresholds.

## Final Approved Feature Contract

The prediction model must use exactly these 11 features:

1. `pH`
2. `Temperature`
3. `Taste`
4. `Odor`
5. `Fat_Content`
6. `Titratable_Acidity`
7. `Protein_Content`
8. `Lactose_Content`
9. `TPC`
10. `SCC`
11. `Color`

The current system uses nine features and must be changed. `SNF` must be removed
from the model input unless it is kept only as non-model metadata. `Turbidity`
must not be added because it is not part of the approved thesis feature
contract.

## Standards and Threshold Sources

Use `US EAS 67:2023` directly for:

- `pH`: 6.6 - 6.8
- `Fat_Content`: minimum 3.25%
- `Titratable_Acidity`: maximum 0.17%
- `TPC`: maximum 2 x 10^6 CFU/ml
- `SCC`: maximum 3 x 10^5
- `Taste`, `Odor`, `Color`: general normal organoleptic characteristics

Use approved supplementary references for:

- `Temperature`: Codex CXC 57-2004 / CAC/RCP 57 milk hygiene guidance
- `Protein_Content`: FAO milk composition reference ranges
- `Lactose_Content`: FAO milk composition reference ranges

Protein and lactose are composition-reference ranges, not direct legal
acceptance limits under `US EAS 67:2023`.

## Definition of Done

The implementation is complete only when:

- [ ] The app uses exactly the 11 approved model features.
- [ ] The app outputs `Low`, `Medium`, or `High`.
- [ ] The dataset contains 1,500 synthesized samples, 500 per class.
- [ ] The dataset generation method is reproducible and documented.
- [ ] The Random Forest model is retrained on the approved 11-feature dataset.
- [ ] SVM, ANN, and XGBoost comparisons are reproducible where required by the
      thesis.
- [ ] The saved model artifact includes feature order, classes, version,
      dataset version, label-policy version, metrics, and dependency versions.
- [ ] Flask rejects incompatible model artifacts.
- [ ] The form, prediction route, result page, history page, Firestore records,
      and exports all use the 11-feature contract.
- [ ] `config/standards.json` is aligned with `US EAS 67:2023` and documented
      supplementary sources.
- [ ] Tests cover dataset generation, feature schema, sensory encoding, model
      loading, prediction, Flask validation, and Firestore record shape.
- [ ] The README and supporting documentation match the approved thesis.

## Phase 1: Baseline and Cleanup

- [x] Create a working branch.
- [x] Record the current model artifact hash and current prediction behavior.
- [x] Archive the current nine-feature model as rollback evidence.
- [x] Document duplicate model artifacts for cleanup during model artifact
      replacement.
- [x] Remove misleading `4class` naming from active paths.
- [x] Remove hard-coded Firebase credentials and debug login output.
- [x] Update `.gitignore` for secrets, logs, generated artifacts, and local
      environment files.
- [x] Credential rotation intentionally deferred by project owner for now.

## Phase 2: Feature and Label Contract

- [x] Create one shared model-contract module defining the 11 features.
- [x] Standardize labels as `Low`, `Medium`, and `High`.
- [x] Replace old `Moderate` references in backend, templates, charts, history,
      Firestore records, and docs.
- [x] Define sensory encoding:
      - `Taste`: normal/acceptable = 1, abnormal/off = 0
      - `Odor`: fresh/normal = 1, abnormal/objectionable = 0
      - `Color`: normal/creamy-white = 1, abnormal = 0
- [x] Document that taste assessment must follow a safe approved protocol and
      the software must not instruct operators to consume unsafe raw milk.

## Phase 3: Standards Configuration

- [x] Replace existing standards with `US EAS 67:2023` values where available.
- [x] Update TPC maximum to `2_000_000`.
- [x] Update SCC maximum to `300_000`.
- [x] Keep pH at `6.6 - 6.8`.
- [x] Keep fat minimum at `3.25`.
- [x] Keep titratable acidity maximum at `0.17`.
- [x] Add organoleptic rules for taste, odor, and color.
- [x] Add documented supplementary ranges for temperature, protein, and lactose.
- [x] Make standards warnings separate from ML predictions.

## Phase 4: Dataset Generation

- [x] Build a reproducible script to generate the 1,500-row synthesized dataset.
- [x] Generate 500 `High`, 500 `Medium`, and 500 `Low` records.
- [x] Use EAS 67:2023 thresholds and documented supplementary assumptions.
- [x] Include all 11 features and one target column.
- [x] Store dataset metadata:
      - source standard;
      - supplementary sources;
      - random seed;
      - generation date;
      - feature units;
      - label-policy version.
- [x] Validate no missing values, duplicates, invalid classes, or impossible
      values.
- [x] Save the final dataset under a clear path, for example:
      `data/milk_quality_eas67_2023_synth_1500.csv`.

## Phase 5: Training and Evaluation

- [x] Replace the ad hoc training script with a root-runnable command.
- [x] Use deterministic random seeds.
- [x] Use stratified 80/20 split: 1,200 training and 300 testing samples.
- [x] Train Random Forest on the 11-feature dataset.
- [x] Evaluate:
      - accuracy;
      - balanced accuracy;
      - macro precision;
      - macro recall;
      - macro F1;
      - per-class metrics;
      - confusion matrix;
      - five-fold cross-validation;
      - learning curve;
      - feature importance.
- [x] Reproduce comparative models available in the current environment:
      - SVM;
      - ANN;
      - XGBoost.
- [x] Confirm whether the thesis-reported 99.5% Random Forest accuracy is
      reproducible. If not, report the actual value honestly.

## Phase 6: Model Artifact

- [x] Save a versioned model bundle, for example:
      `ml_model/artifacts/milk_quality_rf_v1.joblib`.
- [x] Include:
      - fitted preprocessing/model pipeline;
      - ordered 11-feature list;
      - class list: `Low`, `Medium`, `High`;
      - sensory encoding map;
      - model version;
      - dataset version;
      - label-policy version;
      - metrics;
      - Python and scikit-learn versions;
      - training timestamp;
      - artifact checksum.
- [x] Make Flask fail startup if the artifact feature list or classes do not
      match the approved contract.

## Phase 7: Flask Backend Integration

- [x] Load the model using an absolute project-relative path.
- [x] Move prediction logic into a dedicated service module.
- [x] Parse and validate all 11 inputs server-side.
- [x] Encode `Taste`, `Odor`, and `Color` consistently with training.
- [x] Build the input DataFrame in the exact model feature order.
- [x] Call `predict()` and `predict_proba()`.
- [x] Map probabilities using `model.classes_`.
- [x] Return label, confidence, probabilities, model version, dataset version,
      and label-policy version.
- [x] Store all prediction metadata with each Firestore record.

## Phase 8: User Interface

- [x] Update the testing form to collect the 11 approved inputs.
- [x] Remove `SNF` from the prediction form or move it to non-model metadata.
- [x] Add controlled inputs for `Taste`, `Odor`, and `Color`.
- [x] Update labels and help text to match the thesis and standards.
- [x] Update result page to display all 11 inputs.
- [x] Update charts to use `Low`, `Medium`, and `High`.
- [x] Update history tables and exports to include the 11 inputs and model
      metadata.
- [x] Ensure old Firestore records with `Moderate` or missing sensory fields do
      not break history views.

## Phase 9: Database and Records

- [x] Update Firestore write structure to include:
      - batch details;
      - all 11 measurements;
      - original sensory labels;
      - encoded sensory values;
      - prediction label;
      - probabilities;
      - confidence;
      - model metadata;
      - standards observations;
      - timestamp.
- [x] Add compatibility handling for legacy records.
- [x] Avoid storing secrets or raw authentication responses.

## Phase 10: Testing

- [x] Dataset schema test.
- [x] Dataset class-balance test.
- [x] Sensory encoding test.
- [x] Standards configuration test.
- [x] Model artifact compatibility test.
- [x] Known-sample prediction test.
- [x] Invalid numeric input test.
- [x] Missing sensory input test.
- [x] Flask route test.
- [x] Firestore record-shape test using test doubles.
- [x] History page legacy-record test.

## Phase 11: Documentation

- [x] Update `README.md`.
- [x] Update model card.
- [x] Update dataset card.
- [x] Document all thresholds and sources.
- [x] Document that the system is a proof-of-concept decision-support tool.
- [x] Document that the synthesized dataset does not replace real plant
      validation.
- [x] Update older planning docs so they describe the approved 11-feature
      contract and avoid stale model-input claims.

## Phase 12: Verification and Demo

- [x] Run the training pipeline from scratch.
- [x] Confirm the saved artifact loads without warnings in the deployed
      dependency versions.
- [ ] Run Flask locally in the user desktop session.
- [x] Submit at least one `High`, one `Medium`, and one `Low` sample through
      the prediction service.
- [x] Confirm Firestore records are shaped correctly using automated test
      doubles.
- [x] Confirm result and history routes render correctly using automated Flask
      route tests.
- [ ] Confirm charts and exports render correctly in the browser.
- [ ] Prepare screenshots matching the approved thesis sections.

Phase 12 evidence is recorded in `docs/verification-demo.md`. The sandbox
reached Flask startup but could not bind the local development port, so the
final browser demo and screenshots must be completed from the user's desktop
terminal.

## Proposed Execution Order

1. Clean plan and contracts.
2. Standards configuration.
3. Dataset generation.
4. Training and evaluation.
5. Model artifact packaging.
6. Backend integration.
7. UI updates.
8. Firestore record updates.
9. Tests.
10. Documentation.
11. Verification demo.

## Progress Log

| Date | Phase | Status | Notes |
| --- | --- | --- | --- |
| 2026-07-18 | Final planning | Complete | Final target set to approved thesis: Flask web prototype, 11 features, 1,500 synthesized EAS 67:2023-bounded samples, and labels `Low`, `Medium`, `High`. |
