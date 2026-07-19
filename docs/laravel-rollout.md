# DairyIQ Laravel Rollout Guide

This guide describes how to run and verify the Laravel DairyIQ application with
the Python Random Forest ML service.

## Local Startup

Use three terminals.

Terminal 1: Python ML service

```bash
cd /home/kawaly/Projects/Laravel_APPs/Diary_IQ
source venv/bin/activate
export ML_SERVICE_TOKEN=local-dev-token
python ml_service.py
```

Expected service URL:

```text
http://127.0.0.1:5100
```

Terminal 2: Laravel backend

```bash
cd /home/kawaly/Projects/Laravel_APPs/Diary_IQ/dairy_iq
php artisan migrate --force
php artisan serve
```

Expected application URL:

```text
http://127.0.0.1:8000
```

Terminal 3: frontend assets

```bash
cd /home/kawaly/Projects/Laravel_APPs/Diary_IQ/dairy_iq
npm run dev
```

## Required Environment Values

Laravel `.env` must include:

```dotenv
ML_SERVICE_URL=http://127.0.0.1:5100
ML_SERVICE_TOKEN=local-dev-token
ML_SERVICE_TIMEOUT=10
```

The Python terminal must use the same `ML_SERVICE_TOKEN` value.

For production, set:

```dotenv
APP_ENV=production
APP_DEBUG=false
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

MySQL is acceptable for the current project because it is already configured
and supports the relational company, user, audit, and milk-batch records. Use
PostgreSQL only if the hosting environment standardizes on it.

## Health Checks

Laravel default uptime route:

```text
GET /up
```

DairyIQ combined health route:

```text
GET /dairyiq/health
```

The combined health route checks:

- Laravel request handling
- database connectivity
- Python ML service availability
- model artifact loaded state
- model version and feature count returned by Python

Python ML service health route:

```text
GET http://127.0.0.1:5100/api/health
```

Expected healthy response from Laravel:

```json
{
  "status": "ok",
  "checks": {
    "laravel": { "status": "ok" },
    "database": { "status": "ok" },
    "ml_service": {
      "status": "ok",
      "model_loaded": true,
      "model_version": "milk_quality_rf_v1",
      "feature_count": 11
    }
  }
}
```

## Demo Checklist

1. Visit `http://127.0.0.1:8000/dairyiq/health`.
2. Log in as the seeded super admin.
3. Create a company and company user.
4. Log in as the company user.
5. Submit High, Medium, and Low prediction samples.
6. Confirm records appear in history.
7. Open a saved result page.
8. Preview and download the PDF report.
9. Confirm another company cannot access the saved result URL.

## Flask Status

The old Flask web UI is now a reference implementation only. The Laravel app is
the user-facing system, while Python remains the internal ML inference service.
Do not add new user-facing features to the old Flask UI unless the thesis scope
changes.
