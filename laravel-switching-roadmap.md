# DairyIQ Laravel Switching Roadmap

## Objective

Switch DairyIQ from the current Flask web application into a Laravel-based
multi-company system, while keeping Python as the dedicated machine learning
service for Random Forest milk quality prediction.

The final architecture will be:

```text
Laravel + Inertia/Vue
- authentication
- company/user management
- batch entry
- history
- reports
- database records
- access control

Python ML service
- model artifact loading
- 11-feature validation
- Random Forest inference
- standards safety gate
- prediction metadata response
```

## Guiding Decision

Do not rewrite the Random Forest model in PHP. Laravel should own the business
application. Python should own ML inference.

## Phase 1: Baseline and Architecture Freeze

- [ ] Keep the current Flask implementation as the working reference system.
- [ ] Confirm the approved 11-feature model contract remains unchanged:
      `pH`, `Temperature`, `Taste`, `Odor`, `Fat_Content`,
      `Titratable_Acidity`, `Protein_Content`, `Lactose_Content`, `TPC`,
      `SCC`, and `Color`.
- [ ] Confirm public labels remain `Low`, `Medium`, and `High`.
- [ ] Confirm Laravel will replace Flask for screens, records, users, reports,
      and history.
- [ ] Confirm Python remains responsible for prediction and model validation.
- [ ] Define environment variables:
      - `ML_SERVICE_URL`
      - `ML_SERVICE_TOKEN`
      - database connection values
      - mail/session/cache settings

## Phase 2: Laravel Domain Foundation

- [x] Create `companies` table.
- [x] Add `company_id` to `users`.
- [x] Create `milk_batches` table.
- [x] Store all 11 approved measurements.
- [x] Store sensory original labels and encoded values.
- [x] Store final prediction, raw ML prediction, probabilities, confidence,
      standards observations, standards gate details, model metadata, and
      timestamps.
- [x] Add Eloquent models and relationships:
      - `Company`
      - `User`
      - `MilkBatch`
- [x] Add policies or query scopes so users only access records for their own
      company.
- [x] Add factories and feature tests.

## Phase 3: Python ML Service Extraction

- [ ] Keep existing training, dataset generation, and model artifact scripts in
      Python.
- [ ] Create a lightweight Python API endpoint:

```text
POST /api/predict
```

- [ ] Request body must contain the 11 approved inputs.
- [ ] Response must include:
      - `prediction`
      - `ml_prediction`
      - `confidence`
      - `probabilities`
      - `standards_observations`
      - `standards_quality_gate`
      - `model_metadata`
      - feature status/colors
- [ ] Add request validation and API-token protection.
- [ ] Add Python tests for valid input, invalid input, and known High/Medium/Low
      samples.

## Phase 4: Laravel Prediction Integration

- [ ] Create Laravel service class:

```text
App\Services\MilkQualityPredictionService
```

- [ ] Laravel validates form input before calling Python.
- [ ] Laravel sends prediction payload to Python using Laravel HTTP client.
- [ ] Laravel handles ML service errors gracefully.
- [ ] Laravel stores the prediction response in SQL.
- [ ] Add feature tests using HTTP fakes so Laravel tests do not depend on the
      live Python service.

## Phase 5: Laravel User Interface

- [ ] Replace starter dashboard with DairyIQ dashboard.
- [ ] Build Inertia/Vue pages:
      - prediction form
      - result page
      - history page
      - report/export page
      - company profile/settings page
- [ ] Form must collect exactly the approved 11 inputs.
- [ ] `SNF` and `Turbidity` must not be model inputs.
- [ ] Result page must show:
      - final prediction
      - raw Random Forest vote
      - confidence
      - class probabilities
      - standards observations
      - standards safety gate explanation
      - model metadata
- [ ] History page must show company-scoped records only.
- [ ] Add pagination, filtering, sorting, and `View Details` links.

## Phase 6: Reporting and Export

- [ ] Rebuild the current executive-summary PDF layout in Laravel.
- [ ] Report pages should follow:
      - Page 1: executive summary and batch details
      - Page 2: measured inputs and standards status
      - Page 3: probabilities, observations, and charts
- [ ] Ensure reports include company name and user/tester.
- [ ] Ensure exported reports are generated from saved database records, not
      unsaved browser-only state.
- [ ] Add tests for report routes and authorization.

## Phase 7: Data Migration

- [ ] Decide whether old Firestore records must be imported.
- [ ] If yes, write an import command:

```text
php artisan dairyiq:import-firestore
```

- [ ] Map legacy `Moderate` to `Medium`.
- [ ] Handle missing sensory fields safely.
- [ ] Attach imported records to a default company.
- [ ] Mark imported records with `source = firestore_legacy`.

## Phase 8: Security and Multi-Company Access

- [ ] Remove Firebase dependency from the Laravel-facing app.
- [ ] Use Laravel authentication and sessions.
- [ ] Add company-level authorization checks.
- [ ] Ensure users cannot access another company's batch result URL.
- [ ] Protect Python ML service so it only accepts trusted Laravel requests.
- [ ] Move secrets to `.env`; never commit service tokens or production keys.

## Phase 9: Testing and Verification

- [ ] Laravel feature tests:
      - authenticated prediction form access
      - unauthenticated redirects
      - company-scoped history
      - user cannot view another company's record
      - prediction request saves all required fields
      - ML service failure shows safe error
      - report route renders saved records
- [ ] Python tests:
      - model artifact compatibility
      - known High/Medium/Low predictions
      - invalid feature rejection
      - standards safety gate behavior
- [ ] End-to-end manual demo:
      - register/login
      - create company/user
      - submit High sample
      - submit Medium sample
      - submit Low sample
      - view history
      - view details
      - export report

## Phase 10: Rollout

- [ ] Run Laravel and Python services locally.
- [ ] Document startup commands.
- [ ] Prepare production deployment strategy.
- [ ] Choose production database: MySQL or PostgreSQL.
- [ ] Configure queue/session/cache storage.
- [ ] Add health checks:
      - Laravel app health
      - Python ML service health
      - model artifact loaded status
- [ ] Archive the Flask web UI after Laravel reaches feature parity.

## Definition of Done

The switch is complete when:

- [ ] Laravel is the only user-facing web application.
- [ ] Python serves predictions through an internal ML API.
- [ ] The 11-feature approved thesis contract is preserved.
- [ ] Records are stored in Laravel SQL tables with company isolation.
- [ ] History and reports use Laravel database records.
- [ ] Users can only access their own company's data.
- [ ] Prediction results match the current Flask/Python behavior.
- [ ] Automated tests cover Laravel and Python integration boundaries.
- [ ] The final demo supports High, Medium, and Low prediction flows.
