# Dataset Card

## Dataset

Dataset name:

`milk_quality_eas67_2023_synth_1500`

Path:

`data/milk_quality_eas67_2023_synth_1500.csv`

Metadata:

`data/milk_quality_eas67_2023_synth_1500.metadata.json`

## Purpose

The dataset supports reproducible development and evaluation of the DairyIQ
proof-of-concept Random Forest classifier.

It is a synthesized, standards-bounded dataset. It does not replace real plant
validation.

## Size and Balance

- total rows: `1,500`
- `Low`: `500`
- `Medium`: `500`
- `High`: `500`

## Columns

The dataset contains exactly the approved 11 model features plus `Label`:

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
12. `Label`

`SNF` and `Turbidity` are intentionally excluded from the model dataset.

## Sources

Primary source:

- `US EAS 67:2023`

Supplementary sources:

- Codex CXC 57-2004 / CAC/RCP 57 for temperature handling assumptions.
- FAO milk composition reference ranges for protein and lactose assumptions.

## Generation

Generation script:

`scripts/generate_dataset.py`

Random seed:

`20260718`

Generation policy:

- `High`: within configured standards and normal sensory values.
- `Medium`: mild numeric deviations and occasional sensory defects.
- `Low`: clear numeric failures and at least one sensory defect.

## Validation

The generator validates:

- approved columns only
- no missing values
- valid labels only
- 500 rows per class
- no duplicate feature rows
- no impossible numeric values
- sensory values encoded as `0` or `1`

## Limitations

This dataset is synthetic. It is appropriate for prototype development and
repeatable thesis demonstrations, but it must not be described as proof of
production-grade real-world performance.
