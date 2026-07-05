#!/bin/sh
set -eu

cd /var/www/html

if [ "${APP_SKIP_BOOTSTRAP:-0}" = "1" ]; then
  echo "[entrypoint] Skipping production bootstrap because APP_SKIP_BOOTSTRAP=1"
else
  echo "[entrypoint] Running production bootstrap"
  php tools/bootstrap_production.php
fi

echo "[entrypoint] Starting Apache"
exec apache2-foreground
