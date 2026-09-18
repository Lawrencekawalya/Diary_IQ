# DairyIQ

Repository:

`https://github.com/Lawrencekawalya/Diary_IQ`

DairyIQ is a Laravel-based milk quality decision-support prototype backed by a
Python Random Forest prediction service. It classifies raw cow milk batch
quality into three classes:

- `Low`
- `Medium`
- `High`

The system is designed for thesis/prototype demonstration and supports
company-scoped batch entry, prediction, standards observations, history,
analytics dashboards, PDF reports, user management, and super-admin company
administration.

The software does not replace laboratory judgment, regulatory certification, or
real dairy plant validation.

## Current Architecture

The current user-facing application is Laravel. The old Flask web interface is
retained only as a historical/reference implementation.

```text
Laravel + Inertia/Vue
- authentication and sessions
- company and user management
- milk batch entry
- prediction history
- dashboards and charts
- PDF report export
- multi-company access control

Python ML service
- validated Random Forest artifact loading
- approved 11-feature input validation
- Random Forest inference
- standards safety gate
- prediction probabilities and confidence response
```

## Technology Stack

Backend/application:

- PHP `^8.3`
- Laravel `^13`
- Laravel Fortify authentication
- Inertia Laravel
- MySQL database
- Dompdf for PDF reports

Frontend:

- Vue 3
- Inertia Vue
- TypeScript
- Vite
- Tailwind CSS
- ApexCharts / vue3-apexcharts
- Lucide icons

Machine learning service:

- Python
- Flask internal API
- Gunicorn for production service hosting
- scikit-learn `RandomForestClassifier`
- pandas / NumPy
- joblib model serialization
- pytest for Python verification

## Approved 11-Feature Model Contract

The production model accepts exactly these 11 features:

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

`Taste`, `Odor`, and `Color` are encoded as binary sensory values:

```text
1 = normal/acceptable
0 = abnormal/defective
```

`SNF` and `Turbidity` are not model inputs in the current approved contract.

## Standards and Reference Sources

The standards gate and synthetic data generation policy are based on:

- `US EAS 67:2023` for raw cow milk specification thresholds.
- Codex CXC 57-2004 / CAC/RCP 57 for supplementary milk hygiene and
  temperature-handling assumptions.
- FAO milk composition references for supplementary protein and lactose ranges.

Configured thresholds are documented in:

`docs/standards-configuration.md`

The standards gate can downgrade unsafe or out-of-range predictions. It does
not upgrade a lower Random Forest vote to a higher quality class.

## Reproducibility Artifacts

The current reproducibility artifacts are included in this repository.

### Synthetic Dataset

Dataset:

`data/milk_quality_eas67_2023_synth_1500.csv`

Metadata:

`data/milk_quality_eas67_2023_synth_1500.metadata.json`

Generation script:

`scripts/generate_dataset.py`

Dataset version:

`milk_quality_eas67_2023_synth_1500_v1`

Dataset size:

```text
1,500 records
500 Low
500 Medium
500 High
```

The dataset is synthetic and standards-bounded. It was generated for
reproducible thesis prototype development and should not be presented as real
client, farmer, or dairy plant production data.

The current dataset generator uses a fixed random seed stored in the dataset
metadata. The current generated dataset records:

```text
random_seed = 20260718
```

The model training and evaluation scripts use:

```text
random_state = 42
```

### Training and Evaluation

Training/evaluation script:

`scripts/train_evaluate.py`

The training protocol validates the 11-feature schema, verifies class balance,
uses a stratified 80/20 split, and performs five-fold stratified
cross-validation.

Evaluation outputs:

`reports/model-evaluation/`

Important files:

- `metrics.json`
- `model_comparison.csv`
- `random_forest_confusion_matrix.csv`
- `random_forest_feature_importance.csv`
- `random_forest_learning_curve.csv`
- `random_forest_phase5_candidate.joblib`

### Production Model Artifact

Artifact builder:

`scripts/build_model_artifact.py`

Production artifact:

`ml_model/artifacts/milk_quality_rf_v1.joblib`

Artifact metadata:

`ml_model/artifacts/milk_quality_rf_v1.metadata.json`

Artifact checksum:

`ml_model/artifacts/milk_quality_rf_v1.joblib.sha256`

The artifact bundle stores:

- fitted Random Forest pipeline
- approved feature order
- supported class list
- sensory encoding map
- dataset version
- label-policy version
- evaluation metrics
- dependency versions
- training timestamp
- checksum metadata

## Data Availability

The DairyIQ source code, synthetic dataset generation script, training pipeline,
trained Random Forest artifact, and prototype application code are available in
this repository:

`https://github.com/Lawrencekawalya/Diary_IQ`

No real operational dairy plant, farmer, or client data was used to train the
current prototype model.

## Local Development

Use three terminals for local development.

### 1. Python ML Service

From the repository root:

```bash
python -m venv venv
source venv/bin/activate
pip install -r requirements.txt
export ML_SERVICE_TOKEN=local-dev-token
python ml_service.py
```

Expected service URL:

```text
http://127.0.0.1:5100
```

Health check:

```bash
curl http://127.0.0.1:5100/api/health
```

Expected response includes:

```json
{
  "status": "ok",
  "model_loaded": true,
  "model_version": "milk_quality_rf_v1",
  "feature_count": 11
}
```

### 2. Laravel Backend

From the Laravel application directory:

```bash
cd dairy_iq
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan serve
```

Laravel `.env` must include:

```dotenv
ML_SERVICE_URL=http://127.0.0.1:5100
ML_SERVICE_TOKEN=local-dev-token
ML_SERVICE_TIMEOUT=30
```

Expected application URL:

```text
http://127.0.0.1:8000
```

### 3. Frontend Assets

From `dairy_iq`:

```bash
npm install
npm run dev
```

For production assets:

```bash
npm run build
```

## Rebuild Dataset, Evaluation, and Artifact

From the repository root:

```bash
source venv/bin/activate
python scripts/generate_dataset.py
python scripts/train_evaluate.py
python scripts/build_model_artifact.py
```

## Tests and Verification

Python tests:

```bash
python -m pytest tests
```

Laravel tests:

```bash
cd dairy_iq
php artisan test
```

Frontend checks:

```bash
cd dairy_iq
npm run types:check
npm run build
```

Laravel formatting:

```bash
cd dairy_iq
vendor/bin/pint
```

## Deployment Notes

The deployed system uses:

- Nginx or Apache serving `dairy_iq/public`
- PHP-FPM for Laravel
- MySQL for application data
- a background `systemd` service for the Python ML service
- Gunicorn binding the ML service internally to `127.0.0.1:5100`

The Python ML service should not be exposed publicly. Laravel calls it using
`ML_SERVICE_URL` and `ML_SERVICE_TOKEN`.

Production Laravel `.env` should set:

```dotenv
APP_ENV=production
APP_DEBUG=false
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
ML_SERVICE_URL=http://127.0.0.1:5100
ML_SERVICE_TOKEN=change-this-token
```

Deployment details are documented in:

`docs/laravel-rollout.md`

## Main Documentation

- `docs/model-contract.md`
- `docs/dataset-card.md`
- `docs/dataset-generation.md`
- `docs/training-evaluation.md`
- `docs/model-artifact.md`
- `docs/standards-configuration.md`
- `docs/laravel-rollout.md`
- `docs/testing.md`

## Important Limitations

- The current training dataset is synthetic.
- The model has not yet been externally validated using real dairy plant
  production or laboratory data.
- Synthetic evaluation results should not be presented as proof of real-world
  production accuracy.
- The system is a decision-support prototype, not a regulatory certification
  tool.
- Taste assessment must follow a safe approved protocol and must not instruct
  operators to consume unsafe raw milk.
