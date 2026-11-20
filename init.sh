#!/bin/bash

set -euo pipefail

cd "$(dirname "$0")"

echo "[init] Installing backend dependencies..."
composer install --no-interaction --prefer-dist

echo "[init] Installing frontend dependencies..."
npm --prefix frontend ci

if [[ ! -f .env ]]; then
    echo "[init] Creating local environment file..."
    cp .env.example .env
    php artisan key:generate
fi

if grep -q '^DB_CONNECTION=sqlite' .env && [[ ! -f database/database.sqlite ]]; then
    echo "[init] Creating local SQLite database file..."
    touch database/database.sqlite
fi

echo "[init] Running migrations..."
php artisan migrate --force

echo "[init] Running backend tests..."
php artisan test

echo "[init] Running frontend checks..."
npm --prefix frontend run typecheck
npm --prefix frontend run lint
npm --prefix frontend run test
npm --prefix frontend run build

echo "[init] Verification complete."
echo "[init] Start the development services with:"
echo "  php artisan serve"
echo "  php artisan reverb:start --host=0.0.0.0 --port=8080"
echo "  npm --prefix frontend run dev"
