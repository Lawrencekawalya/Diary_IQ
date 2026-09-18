# DairyIQ - Milk Quality Prediction and Analytics System

**DairyIQ** is a machine learning-powered web application for raw cow milk
quality prediction, batch record management, standards-based observations,
analytics, and PDF reporting.

The system uses a **Laravel + Inertia/Vue** application for the user-facing
platform and a dedicated **Python Random Forest ML service** for prediction.
It classifies milk quality into:

- **Low**
- **Medium**
- **High**

Repository: **https://github.com/Lawrencekawalya/Diary_IQ**

---

## Overview

DairyIQ supports dairy collection and processing teams by recording milk-batch
details, evaluating approved quality parameters, running a trained Random
Forest classifier, and generating structured prediction reports.

The application includes:

- multi-company user access;
- milk batch entry and prediction;
- historical prediction records;
- dashboard analytics and charts;
- standards observations based on configured milk-quality thresholds;
- PDF report preview and download;
- super-admin company and user management;
- a protected Python ML service for model inference.

> DairyIQ is a decision-support prototype. It does not replace laboratory
> testing, regulatory certification, or real plant validation.

---

## Key Features

- **Three-class prediction:** Low, Medium, and High milk quality.
- **Approved 11-feature model contract:** exactly the features used by the
  trained Random Forest artifact.
- **Standards-aware decision support:** standards checks can downgrade unsafe
  predictions where required.
- **Company-scoped records:** users only access records belonging to their own
  company workspace.
- **Role-based access:** super admin, company admin, and tester roles.
- **Interactive dashboards:** quality trends, district performance, batch
  summaries, and quality distribution charts.
- **PDF reports:** saved batch results can be previewed and exported.
- **Reproducible ML pipeline:** dataset generation, model evaluation, and model
  artifact building scripts are included.

---

## Technology Stack

| Layer | Technology | Purpose |
| --- | --- | --- |
| Application backend | Laravel `^13`, PHP `^8.3` | Authentication, routing, records, reports, authorization |
| Frontend | Vue 3, Inertia.js, TypeScript, Tailwind CSS | User interface and dashboards |
| Charts | ApexCharts / vue3-apexcharts | Dashboard and result visualizations |
| Database | MySQL | Companies, users, milk batches, audit records |
| Reports | barryvdh/laravel-dompdf | PDF report generation |
| ML service | Python, Flask, Gunicorn | Internal prediction API |
| ML model | scikit-learn RandomForestClassifier | Milk quality classification |
| Data processing | pandas, NumPy | Dataset generation and training pipeline |
| Model persistence | joblib | Versioned Random Forest artifact |
| Testing | pytest, Laravel tests, vue-tsc, ESLint, Pint | Backend, ML, and frontend verification |

---

## System Architecture

```text
Browser
   |
   v
Laravel + Inertia/Vue Application
   |
   |-- MySQL database
   |     - companies
   |     - users
   |     - milk_batches
   |     - audit logs
   |
   |-- PDF report generator
   |
   v
Internal Python ML Service
   |
   |-- validates approved 11 inputs
   |-- loads Random Forest artifact
   |-- applies standards safety gate
   |-- returns prediction, confidence, and probabilities
```

The Python ML service is internal and should run on `127.0.0.1`. It is called
by Laravel using `ML_SERVICE_URL` and `ML_SERVICE_TOKEN`.

---

## Approved Model Inputs

The current production model accepts exactly these 11 inputs:

| # | Feature | Description |
| ---: | --- | --- |
| 1 | `pH` | Milk pH value |
| 2 | `Temperature` | Milk temperature in degrees Celsius |
| 3 | `Taste` | Encoded sensory taste status |
| 4 | `Odor` | Encoded sensory odor status |
| 5 | `Fat_Content` | Fat percentage |
| 6 | `Titratable_Acidity` | Acidity as % lactic acid |
| 7 | `Protein_Content` | Protein percentage |
| 8 | `Lactose_Content` | Lactose percentage |
| 9 | `TPC` | Total Plate Count |
| 10 | `SCC` | Somatic Cell Count |
| 11 | `Color` | Encoded sensory color status |

Sensory values are encoded as:

```text
1 = normal / acceptable
0 = abnormal / defective
```

`SNF` and `Turbidity` are **not** part of the current model contract.

---

## Standards and Sources

The standards configuration is based on:

- **US EAS 67:2023** - raw cow milk specification thresholds;
- **Codex CXC 57-2004 / CAC/RCP 57** - supplementary milk hygiene and
  temperature-handling guidance;
- **FAO milk composition references** - supplementary protein and lactose
  assumptions.

Standards thresholds are documented in:

```text
docs/standards-configuration.md
```

The standards gate is used as a safety layer. It can prevent unsafe or
out-of-range samples from being reported above an acceptable quality level, but
it does not automatically upgrade a lower Random Forest prediction to High.

---

## Reproducibility Artifacts

The repository includes the dataset generation script, generated synthetic
dataset, training/evaluation script, evaluation outputs, and trained model
artifact.

### Dataset

| Item | Path / Value |
| --- | --- |
| Dataset file | `data/milk_quality_eas67_2023_synth_1500.csv` |
| Metadata file | `data/milk_quality_eas67_2023_synth_1500.metadata.json` |
| Generation script | `scripts/generate_dataset.py` |
| Dataset version | `milk_quality_eas67_2023_synth_1500_v1` |
| Total records | `1,500` |
| Class balance | `500 Low`, `500 Medium`, `500 High` |
| Dataset generation seed | `20260718` |
| Training/evaluation random state | `42` |

The dataset is synthetic and standards-bounded. It was generated for
reproducible thesis prototype development and should not be presented as real
client, farmer, or dairy plant production data.

### Training and Evaluation

Training script:

```text
scripts/train_evaluate.py
```

Evaluation outputs:

```text
reports/model-evaluation/
```

Important files:

- `metrics.json`
- `model_comparison.csv`
- `random_forest_confusion_matrix.csv`
- `random_forest_feature_importance.csv`
- `random_forest_learning_curve.csv`
- `random_forest_phase5_candidate.joblib`

### Production Model Artifact

| Item | Path |
| --- | --- |
| Artifact builder | `scripts/build_model_artifact.py` |
| Production artifact | `ml_model/artifacts/milk_quality_rf_v1.joblib` |
| Artifact metadata | `ml_model/artifacts/milk_quality_rf_v1.metadata.json` |
| Artifact checksum | `ml_model/artifacts/milk_quality_rf_v1.joblib.sha256` |

The artifact bundle stores the fitted Random Forest pipeline, approved feature
order, supported classes, sensory encoding map, dataset version, evaluation
metrics, dependency versions, training timestamp, and checksum metadata.

---

## Project Structure

```text
Diary_IQ/
├── app/                              # Legacy Flask reference application
├── config/
│   └── standards.json                # Standards threshold configuration
├── dairy_iq/                         # Current Laravel application
│   ├── app/
│   ├── database/
│   ├── resources/js/
│   ├── resources/views/
│   ├── routes/
│   ├── composer.json
│   └── package.json
├── data/
│   ├── milk_quality_eas67_2023_synth_1500.csv
│   └── milk_quality_eas67_2023_synth_1500.metadata.json
├── docs/                             # Technical documentation
├── ml_model/
│   └── artifacts/
│       ├── milk_quality_rf_v1.joblib
│       ├── milk_quality_rf_v1.metadata.json
│       └── milk_quality_rf_v1.joblib.sha256
├── reports/model-evaluation/         # Evaluation outputs
├── scripts/
│   ├── generate_dataset.py
│   ├── train_evaluate.py
│   └── build_model_artifact.py
├── tests/                            # Python tests
├── ml_service.py                     # Python ML API service
├── prediction_service.py             # Prediction and standards gate logic
├── model_contract.py                 # Approved feature and label contract
├── model_artifact.py                 # Artifact validation
├── requirements.txt
└── README.md
```

---

## Local Setup

Use three terminals during development.

### 1. Start the Python ML Service

```bash
git clone https://github.com/Lawrencekawalya/Diary_IQ.git
cd Diary_IQ

python -m venv venv
source venv/bin/activate
pip install -r requirements.txt

export ML_SERVICE_TOKEN=local-dev-token
python ml_service.py
```

Health check:

```bash
curl http://127.0.0.1:5100/api/health
```

Expected response:

```json
{
  "status": "ok",
  "model_loaded": true,
  "model_version": "milk_quality_rf_v1",
  "feature_count": 11
}
```

### 2. Start the Laravel Application

```bash
cd dairy_iq
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan serve
```

Required Laravel `.env` values:

```dotenv
ML_SERVICE_URL=http://127.0.0.1:5100
ML_SERVICE_TOKEN=local-dev-token
ML_SERVICE_TIMEOUT=30
```

Open:

```text
http://127.0.0.1:8000
```

### 3. Start Frontend Assets

```bash
cd dairy_iq
npm install
npm run dev
```

For production build:

```bash
npm run build
```

---

## Rebuild Dataset and Model

From the repository root:

```bash
source venv/bin/activate
python scripts/generate_dataset.py
python scripts/train_evaluate.py
python scripts/build_model_artifact.py
```

---

## Testing and Verification

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

---

## Deployment Summary

The production deployment uses:

- web server pointing to `dairy_iq/public`;
- PHP-FPM for Laravel;
- MySQL for records;
- a `systemd` service for the Python ML API;
- Gunicorn bound internally to `127.0.0.1:5100`;
- Laravel `.env` values for `ML_SERVICE_URL` and `ML_SERVICE_TOKEN`.

The Python ML service should not be exposed publicly.

Detailed rollout notes are available in:

```text
docs/laravel-rollout.md
```

---

## Data Availability

The DairyIQ source code, synthetic dataset generation script, training pipeline,
trained Random Forest artifact, and prototype application code are available in
this public repository:

```text
https://github.com/Lawrencekawalya/Diary_IQ
```

No real operational dairy plant, farmer, or client data was used to train the
current prototype model.

---

## Documentation

- `docs/model-contract.md`
- `docs/dataset-card.md`
- `docs/dataset-generation.md`
- `docs/training-evaluation.md`
- `docs/model-artifact.md`
- `docs/standards-configuration.md`
- `docs/laravel-rollout.md`
- `docs/testing.md`

---

## Limitations

- The training dataset is synthetic.
- The model has not yet been externally validated using real dairy plant or
  laboratory data.
- Synthetic evaluation results should not be presented as proof of real-world
  production accuracy.
- The system is a decision-support prototype, not a regulatory certification
  tool.
- Taste assessment must follow a safe approved protocol and must not instruct
  operators to consume unsafe raw milk.

---

## Author

Developed by **[@Lawrencekawalya](https://github.com/Lawrencekawalya)**

For inquiries: `kawalya.lawrence2016@gmail.com`
