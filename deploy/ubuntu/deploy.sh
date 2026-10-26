#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "${ROOT}"

if [[ -d .git ]]; then
  git pull --ff-only
fi

cd "${ROOT}/backend"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan view:cache

if command -v supervisorctl >/dev/null 2>&1; then
  sudo supervisorctl restart ritelin-reverb
fi

if command -v systemctl >/dev/null 2>&1; then
  sudo systemctl reload php8.3-fpm
fi

echo "Deploy selesai."
