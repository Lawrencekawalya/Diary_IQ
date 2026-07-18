# Model Card

## Model

Model name:

`milk_quality_rf_v1`

Model type:

Random Forest classifier inside a scikit-learn pipeline.

Artifact:

`ml_model/artifacts/milk_quality_rf_v1.joblib`

## Intended Use

The model supports proof-of-concept milk quality classification for the DairyIQ
Flask system. It predicts one of:

- `Low`
- `Medium`
- `High`

The model is for decision support only. It does not replace laboratory judgment,
regulatory testing, or real plant validation.

## Inputs

The model uses exactly 11 features:

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

`Taste`, `Odor`, and `Color` are binary encoded according to
`model_contract.py`.

## Training Data

Dataset:

`data/milk_quality_eas67_2023_synth_1500.csv`

Dataset version:

`milk_quality_eas67_2023_synth_1500_v1`

The dataset contains 1,500 synthesized samples with 500 samples per class.

## Evaluation

Current Random Forest evaluation:

- accuracy: `1.0000`
- balanced accuracy: `1.0000`
- macro precision: `1.0000`
- macro recall: `1.0000`
- macro F1: `1.0000`

The result is based on the generated synthetic dataset. It should not be
presented as proof of real-world plant performance.

The thesis-reported `99.5%` accuracy was not reproduced exactly on the current
generated dataset and split. The reproducible current result is `100.0%`.

## Limitations

- The training dataset is synthetic.
- Real plant measurements are still required for external validation.
- The model can reproduce the generation policy strongly because the synthetic
  class boundaries are intentionally structured.
- Taste assessment must follow a safe approved protocol and must not instruct
  operators to consume unsafe raw milk.
