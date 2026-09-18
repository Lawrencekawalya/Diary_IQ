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

- [x] Keep the current Flask implementation as the working reference system.
- [x] Confirm the approved 11-feature model contract remains unchanged:
      `pH`, `Temperature`, `Taste`, `Odor`, `Fat_Content`,
      `Titratable_Acidity`, `Protein_Content`, `Lactose_Content`, `TPC`,
      `SCC`, and `Color`.
- [x] Confirm public labels remain `Low`, `Medium`, and `High`.
- [x] Confirm Laravel will replace Flask for screens, records, users, reports,
      and history.
- [x] Confirm Python remains responsible for prediction and model validation.
- [x] Define environment variables:
      - `ML_SERVICE_URL`
      - `ML_SERVICE_TOKEN`
      - database connection values
      - mail/session/cache settings

### Phase 1 Frozen Decisions

- The Flask implementation remains the reference until Laravel reaches feature
  parity.
- Laravel will become the only user-facing application.
- Python will expose prediction through an internal ML API and will remain the
  owner of model loading, validation, Random Forest inference, and standards
  checks.
- The model contract is exactly the approved thesis 11-feature contract:
  `pH`, `Temperature`, `Taste`, `Odor`, `Fat_Content`,
  `Titratable_Acidity`, `Protein_Content`, `Lactose_Content`, `TPC`, `SCC`,
  and `Color`.
- Public prediction labels are frozen as `Low`, `Medium`, and `High`.
- `SNF` and `Turbidity` are not model inputs in the Laravel switch.

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

- [x] Keep existing training, dataset generation, and model artifact scripts in
      Python.
- [x] Create a lightweight Python API endpoint:

```text
POST /api/predict
```

- [x] Request body must contain the 11 approved inputs.
- [x] Response must include:
      - `prediction`
      - `ml_prediction`
      - `confidence`
      - `probabilities`
      - `standards_observations`
      - `standards_quality_gate`
      - `model_metadata`
      - feature status/colors
- [x] Add request validation and API-token protection.
- [x] Add Python tests for valid input, invalid input, and known High/Medium/Low
      samples.

## Phase 4: Laravel Prediction Integration

- [x] Create Laravel service class:

```text
App\Services\MilkQualityPredictionService
```

- [x] Laravel validates form input before calling Python.
- [x] Laravel sends prediction payload to Python using Laravel HTTP client.
- [x] Laravel handles ML service errors gracefully.
- [x] Laravel stores the prediction response in SQL.
- [x] Add feature tests using HTTP fakes so Laravel tests do not depend on the
      live Python service.

## Phase 5: Laravel User Interface

- [x] Replace starter dashboard with DairyIQ dashboard.
- [x] Build Inertia/Vue pages:
      - [x] prediction form
      - [x] result page
      - [x] history page
      - [x] report/export entry page
      - [x] company profile/settings page
- [x] Form must collect exactly the approved 11 inputs.
- [x] `SNF` and `Turbidity` must not be model inputs.
- [x] Result page must show:
      - final prediction
      - raw Random Forest vote
      - confidence
      - class probabilities
      - standards observations
      - standards safety gate explanation
      - model metadata
- [x] History page must show company-scoped records only.
- [x] Add pagination, filtering, sorting, and `View Details` links.

## Phase 6: Reporting and Export

- [x] Rebuild the current executive-summary PDF layout in Laravel.
- [x] Report pages should follow:
      - Page 1: executive summary and batch details
      - Page 2: measured inputs and standards status
      - Page 3: probabilities, observations, and charts
- [x] Ensure reports include company name and user/tester.
- [x] Ensure exported reports are generated from saved database records, not
      unsaved browser-only state.
- [x] Add tests for report routes and authorization.

## Phase 7: Data Migration

- [x] Decide whether old Firestore records must be imported.
- [x] Write an import command:

```text
php artisan dairyiq:import-firestore /path/to/firestore-milk-batches.json --company-id=1
```

- [x] Map legacy `Moderate` to `Medium`.
- [x] Handle missing sensory fields safely.
- [x] Attach imported records to a selected company, or create the default
      `Legacy Firestore Imports` company when no company is supplied.
- [x] Mark imported records with `source = firestore_legacy`.
- [x] Support `--dry-run` validation before writing records.
- [x] Add importer tests for successful import, dry-run, skipped invalid rows,
      legacy Firestore typed JSON, label mapping, and missing sensory defaults.

## Phase 8: Security and Multi-Company Access

- [x] Remove Firebase dependency from the Laravel-facing app.
- [x] Use Laravel authentication and sessions.
- [x] Add role-based access:
      - `super_admin` creates companies and company users.
      - `company_admin` manages users and settings inside one company.
      - `tester` can submit and view prediction records for the assigned
        company.
- [x] Add company-level authorization checks.
- [x] Ensure users cannot access another company's batch result URL.
- [x] Prevent users without a company from creating prediction records.
- [x] Protect Python ML service so it only accepts trusted Laravel requests.
- [x] Move Laravel/Python ML service configuration to `.env`.
- [ ] Rotate any development or legacy secrets before production deployment.

### Phase 8 Implementation Notes

- Laravel is now the user-facing authentication and session layer.
- Super-admin company administration is available at `/admin/companies`.
- Company-admin user management is available at `/company/users`.
- Direct URL access is blocked for unauthorized roles.
- Prediction records remain scoped by `company_id`.
- The old Flask/Firebase application may remain as a reference, but it is not
  part of the Laravel-facing workflow.

## Phase 9: Testing and Verification

- [x] Laravel feature tests:
      - authenticated prediction form access
      - unauthenticated redirects
      - company-scoped history
      - user cannot view another company's record
      - prediction request saves all required fields
      - ML service failure shows safe error
      - report route renders saved records
      - admin role/access controls
      - super-admin company/user management
      - forced password-change flow
      - admin company dashboard view
- [x] Python tests:
      - model artifact compatibility
      - known High/Medium/Low predictions
      - invalid feature rejection
      - standards safety gate behavior
- [x] Frontend verification:
      - TypeScript check
      - ESLint check
      - production Vite build
- [ ] End-to-end manual demo:
      - register/login
      - create company/user
      - reset user password and complete forced password change
      - verify super-admin admin dashboard
      - submit High sample
      - submit Medium sample
      - submit Low sample
      - view history
      - view details
      - export report

### Phase 9 Verification Results

- `php artisan test --compact`: passed, 79 tests and 460 assertions.
- `python -m pytest tests -q`: passed, 32 tests.
- `npm run types:check`: passed.
- `npx eslint resources/js --ext .ts,.vue`: passed.
- `npm run build`: passed. Vite reported only non-blocking warnings for the
  optional `fontaine` package and large chart-related chunks.

## Phase 10: Rollout

- [x] Run Laravel and Python services locally.
- [x] Document startup commands.
- [x] Prepare production deployment strategy.
- [x] Choose production database: MySQL or PostgreSQL.
- [x] Configure queue/session/cache storage.
- [x] Add health checks:
      - Laravel app health
      - Python ML service health
      - model artifact loaded status
- [x] Archive the Flask web UI after Laravel reaches feature parity.

### Phase 10 Implementation Notes

- Local startup and rollout guidance is documented in
  `docs/laravel-rollout.md`.
- Laravel exposes the default `/up` route and the DairyIQ combined health route
  at `/dairyiq/health`.
- `/dairyiq/health` checks Laravel request handling, database connectivity,
  Python ML service availability, model loaded state, model version, and the
  11-feature artifact count.
- MySQL remains the selected production database because the Laravel app already
  uses relational company, user, audit, and milk-batch records.
- Production environment guidance is:
      - `APP_ENV=production`
      - `APP_DEBUG=false`
      - `SESSION_DRIVER=database`
      - `CACHE_STORE=database`
      - `QUEUE_CONNECTION=database`
- The old Flask web UI is archived logically as a reference implementation.
  Laravel is now the user-facing application; Python remains the internal ML
  inference service.

## Phase 11: Branding

- [x] Reuse the old Flask DairyIQ image assets in the Laravel application.
- [x] Replace the starter Laravel icon in the sidebar and auth layouts.
- [x] Replace browser favicon/apple-touch-icon references with DairyIQ assets.
- [x] Apply the old DairyIQ palette to Laravel theme variables:
      - primary blue: `#1f3a93`
      - secondary blue: `#3498db`
      - success green: `#2ecc71`
      - warning orange: `#f39c12`
      - danger red: `#e74c3c`
- [x] Verify the branded frontend compiles successfully.

### Phase 11 Implementation Notes

- Old Flask assets were copied into `dairy_iq/public/brand`.
- The active Laravel logo now uses `/brand/dairyiq-logo-white.png`.
- The active browser icon now uses `/brand/dairyiq-favicon.png`.
- The old Flask assets remain in `app/static` as reference copies.

## Definition of Done

The switch is complete when:

- [x] Laravel is the only user-facing web application.
- [x] Python serves predictions through an internal ML API.
- [x] The 11-feature approved thesis contract is preserved.
- [x] Records are stored in Laravel SQL tables with company isolation.
- [x] History and reports use Laravel database records.
- [x] Users can only access their own company's data.
- [x] Prediction results match the current Flask/Python behavior.
- [x] Automated tests cover Laravel and Python integration boundaries.
- [x] The final demo supports High, Medium, and Low prediction flows.

### Definition of Done Acceptance Notes

- Laravel now owns authentication, roles, companies, prediction entry, result
  pages, history, dashboards, reports, exports, admin tools, and documentation.
- The old Flask web UI is no longer part of the active user-facing workflow.
  It remains only as a reference implementation.
- Python remains active as the internal Random Forest ML service through
  `POST /api/predict` and health reporting through `GET /api/health`.
- Laravel connects to Python through `ML_SERVICE_URL`, `ML_SERVICE_TOKEN`, and
  `ML_SERVICE_TIMEOUT`.
- The approved 11-feature contract is preserved:
  `pH`, `Temperature`, `Taste`, `Odor`, `Fat_Content`,
  `Titratable_Acidity`, `Protein_Content`, `Lactose_Content`, `TPC`, `SCC`,
  and `Color`.
- `SNF` and `Turbidity` remain excluded from the active model contract.
- SQL records are stored in `milk_batches` with `company_id`, and all active
  history, report, dashboard, and result reads are company-scoped.
- Super-admins manage companies and users, while company users remain isolated
  to their assigned company.
- The final rollout guide is documented in `docs/laravel-rollout.md`.
- Automated verification covers Laravel feature behavior, Python model/service
  behavior, frontend typing/build, and the Laravel-to-Python integration
  boundary.
