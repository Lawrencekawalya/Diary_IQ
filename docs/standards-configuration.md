# Standards Configuration

This document records the standards and assumptions used by
`config/standards.json` for the thesis-aligned DairyIQ rebuild.

## Source Priority

Primary standard:

- `US EAS 67:2023`

Supplementary references used where `US EAS 67:2023` does not provide a direct
numeric threshold:

- Codex CXC 57-2004 / CAC/RCP 57 milk hygiene guidance for temperature control.
- FAO milk composition reference ranges for protein and lactose.

Protein and lactose are composition-reference ranges. They are not direct legal
acceptance limits under `US EAS 67:2023`.

## Active Model Standards

| Feature | Configured range | Source basis |
| --- | --- | --- |
| `pH` | `6.6 - 6.8` | `US EAS 67:2023` |
| `Temperature` | `<= 8 deg C` | Codex supplementary hygiene guidance |
| `Taste` | normal / acceptable encoded as `1` | `US EAS 67:2023` organoleptic requirement |
| `Odor` | fresh / normal encoded as `1` | `US EAS 67:2023` organoleptic requirement |
| `Fat_Content` | `>= 3.25%` | `US EAS 67:2023` |
| `Titratable_Acidity` | `<= 0.17% lactic acid` | `US EAS 67:2023` |
| `Protein_Content` | `3.0 - 3.5%` | FAO supplementary composition range |
| `Lactose_Content` | `4.5 - 5.2%` | FAO supplementary composition range |
| `TPC` | `<= 2,000,000 CFU/ml` | `US EAS 67:2023` |
| `SCC` | `<= 300,000 cells/ml` | `US EAS 67:2023` |
| `Color` | normal creamy-white appearance encoded as `1` | `US EAS 67:2023` organoleptic requirement |

## Excluded From Model Standards

`SNF` is excluded from the active model standards because it is not part of the
approved 11-feature model contract. It may be retained only as non-model
operational metadata if the final workflow needs it.

`Turbidity` is excluded because it is not part of the approved thesis feature
contract.

## Prediction Separation

Standards observations are not the same thing as the machine-learning
prediction. The Flask backend stores:

- `prediction`: the Random Forest class label.
- `standards_observations`: threshold-based observations generated from
  `config/standards.json`.

This separation prevents standards warnings from being presented as if they are
the model output.
