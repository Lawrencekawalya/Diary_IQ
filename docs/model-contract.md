# DairyIQ Model Contract

This document defines the approved thesis-aligned model contract for DairyIQ.
All dataset generation, training, Flask validation, Firestore records, exports,
and tests must use this contract.

## Approved Features

The final model must use exactly these 11 inputs, in this order:

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

`SNF` is not a model input under the approved thesis contract. It may only be
stored as non-model metadata if the final workflow still needs it.

`Turbidity` must not be added because it is not part of the approved thesis
feature contract.

## Approved Labels

The final model must output exactly:

1. `Low`
2. `Medium`
3. `High`

The old `Moderate` label is legacy terminology. Runtime display code may map
legacy `Moderate` records to `Medium` for compatibility, but new training data,
new model artifacts, and new Firestore writes must use `Medium`.

## Sensory Encoding

The approved sensory inputs are encoded as binary values:

| Feature | Acceptable value | Encoded value | Unacceptable value | Encoded value |
| --- | --- | ---: | --- | ---: |
| `Taste` | normal / acceptable | `1` | abnormal / off-taste | `0` |
| `Odor` | fresh / normal | `1` | abnormal / objectionable | `0` |
| `Color` | normal / creamy-white | `1` | abnormal appearance | `0` |

Taste assessment must follow a safe approved protocol. The software must not
instruct operators to consume unsafe raw milk.

## Implementation Source

The code source of truth is:

`model_contract.py`

Do not duplicate feature order, class labels, or sensory mappings in training
scripts or Flask routes. Import them from the shared contract module.
