#!/bin/bash
# railway/run-cron.sh
# Used by Railway cron services. Set CRON_COMMAND env var per service.
# Examples: smm:sync-orders | smm:ai-maintenance --health | smm:ai-maintenance
set -e
echo "[$(date -u '+%Y-%m-%d %H:%M:%S UTC')] Running: php artisan ${CRON_COMMAND}"
cd /app
php artisan ${CRON_COMMAND} --no-interaction
echo "[$(date -u '+%Y-%m-%d %H:%M:%S UTC')] Done: ${CRON_COMMAND}"
