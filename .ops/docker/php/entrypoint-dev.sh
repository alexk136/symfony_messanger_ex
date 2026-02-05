#!/bin/bash
set -e

echo "Waiting for database to be ready..."
until PGPASSWORD="${POSTGRES_PASSWORD:-!ChangeMe!}" psql -h "database" -U "${POSTGRES_USER:-app}" -d "${POSTGRES_DB:-app}" -c '\q' 2>/dev/null; do
  echo "Database is unavailable - sleeping"
  sleep 2
done

echo "Database is ready!"

echo "Drop database schema..."
php bin/console doctrine:schema:drop --force || true

echo "Creating database schema..."
php bin/console doctrine:schema:create || true

echo "Starting WebSocket server in background..."
nohup php bin/console websocket:server start > /tmp/websocket.log 2>&1 &

echo "Starting PHP-FPM..."
exec "$@"
