#!/usr/bin/env bash
# Start KCAM for local development: database container, migrations, app server and Vite.
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

CONTAINER="${KCAM_DB_CONTAINER:-kcam-mysql}"

if [ ! -f .env ]; then
    echo "Missing .env. Run: cp .env.example .env && php artisan key:generate" >&2
    exit 1
fi

if command -v docker >/dev/null 2>&1 && docker inspect "$CONTAINER" >/dev/null 2>&1; then
    if [ "$(docker inspect -f '{{.State.Running}}' "$CONTAINER")" != "true" ]; then
        echo "Starting database container $CONTAINER..."
        docker start "$CONTAINER" >/dev/null
    fi
    echo "Waiting for MySQL..."
    for _ in $(seq 1 30); do
        if docker exec "$CONTAINER" mysqladmin ping --silent >/dev/null 2>&1; then
            break
        fi
        sleep 1
    done
else
    echo "Container $CONTAINER not found, assuming MySQL is already running per .env."
fi

[ -d vendor ] || composer install
[ -d node_modules ] || npm install

php artisan migrate --force --no-interaction

echo "KCAM running at http://127.0.0.1:8000 (Ctrl+C to stop)"
exec composer run dev
