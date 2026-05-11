#!/bin/bash
# railway/run-worker.sh — Queue worker for Railway (always-on service)
set -e
echo "[$(date -u '+%Y-%m-%d %H:%M:%S UTC')] Starting queue worker..."
cd /app
exec php artisan queue:work database --tries=3 --timeout=60 --sleep=3 --max-jobs=500 --no-interaction -v
