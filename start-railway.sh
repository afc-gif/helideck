#!/usr/bin/env bash
set -euo pipefail

cleanup() {
  if [[ -n "${LARAVEL_PID:-}" ]]; then
    kill "$LARAVEL_PID" 2>/dev/null || true
  fi
}

trap cleanup EXIT INT TERM

cd backend
php artisan migrate --force
php artisan db:seed --class=InitUsersSeeder --force
php artisan serve --host=127.0.0.1 --port="${BACKEND_PORT:-8000}" &
LARAVEL_PID=$!

cd ..
API_UPSTREAM="http://127.0.0.1:${BACKEND_PORT:-8000}" node server.js
