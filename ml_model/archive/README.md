# Legacy Model Archive

This directory stores rollback evidence for model artifacts that existed before
the approved thesis alignment work.

Current archived artifact:

`dairy_model_4class_legacy_9feature_2026-07-18_sha42a86b97.pkl`

It is the previous active Flask artifact from `ml_model/dairy_model_4class.pkl`.
The artifact uses nine model inputs and outputs `High`, `Low`, and `Moderate`,
so it is retained only for baseline comparison and rollback evidence.

The same legacy artifact is also available at
`ml_model/dairy_model_legacy_9feature.pkl` as the temporary active Flask model
until the thesis-aligned 11-feature artifact replaces it.
