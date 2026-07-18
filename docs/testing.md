# Testing

Phase 10 adds a pytest suite for the thesis-aligned DairyIQ implementation.

Run all tests from the repository root:

```bash
python -m pytest tests
```

## Coverage

The test suite covers:

- approved 11-feature model contract
- `Low`, `Medium`, `High` label contract
- sensory encoding for `Taste`, `Odor`, and `Color`
- generated dataset schema
- generated dataset class balance
- dataset metadata consistency
- standards configuration thresholds
- excluded `SNF` and `Turbidity` standards
- model artifact compatibility validation
- rejection of incompatible artifact classes
- known-sample prediction
- invalid numeric input rejection
- missing sensory input rejection
- Flask `/predict` route using a Firestore test double
- Firestore record shape
- secret/auth field storage guard
- legacy `Moderate` history compatibility

## Current Result

Current test result:

```text
22 passed
```
