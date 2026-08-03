#!/bin/bash
set -e

echo "======================================"
echo "  Newspaper Distribution System"
echo "  Container Startup"
echo "======================================"

cd /var/www/html

# -------------------------------------------------------
# 1. Guarantee .env exists — Laravel & artisan need it
# -------------------------------------------------------
if [ ! -f .env ]; then
    echo "[INFO] .env missing — creating from .env.example..."
    cp .env.example .env 2>/dev/null || touch .env
fi

# -------------------------------------------------------
# 2. Write APP_KEY into .env if not already set
# -------------------------------------------------------
CURRENT_KEY=$(grep -E '^APP_KEY=.+' .env 2>/dev/null | head -1 || true)

if [ -z "$APP_KEY" ] && [ -z "$CURRENT_KEY" ]; then
    echo "[INFO] Generating application key..."
    php artisan key:generate --force --no-interaction
    # Read the generated key back out and EXPORT it so Apache inherits it.
    # OS env vars take precedence over .env in Laravel — without this export,
    # the empty APP_KEY from docker-compose would override the generated one.
    APP_KEY=$(grep '^APP_KEY=' .env | cut -d '=' -f2-)
    export APP_KEY
    echo "[INFO] APP_KEY set: ${APP_KEY:0:12}..."
elif [ -n "$APP_KEY" ]; then
    # Docker-compose provided a key — write it into .env too for consistency
    if grep -q '^APP_KEY=' .env; then
        sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
    else
        echo "APP_KEY=${APP_KEY}" >> .env
    fi
fi

# -------------------------------------------------------
# 3. Wait for database to be ready
# -------------------------------------------------------
echo "[INFO] Waiting for database (${DB_HOST:-postgres}:${DB_PORT:-5432})..."
until php -r "
try {
    new PDO(
        'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null; do
    echo "[INFO] Database not ready yet, retrying in 3s..."
    sleep 3
done
echo "[INFO] Database is ready."

# -------------------------------------------------------
# 4. Ensure all required Laravel directories exist
# -------------------------------------------------------
mkdir -p storage/framework/views \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/testing \
         storage/logs \
         storage/app/public \
         bootstrap/cache

# -------------------------------------------------------
# 5. Laravel bootstrap
# -------------------------------------------------------
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo "[INFO] Running migrations..."
php artisan migrate --force --no-interaction

# -------------------------------------------------------
# 5b. Seed a fresh database
# -------------------------------------------------------
# Migrations create empty tables, so without this a brand-new stack has zero
# users and the default admin@example.com login cannot work. Only seeds when the
# users table is empty, so an existing database is never touched.
# DB_SEED=always forces it; DB_SEED=never skips it.
NEEDS_SEED=$(php -r "
try {
    \$pdo = new PDO(
        'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
    echo \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() == 0 ? 'yes' : 'no';
} catch (Exception \$e) {
    echo 'no';
}
" 2>/dev/null)

if [ "$DB_SEED" = "never" ]; then
    echo "[INFO] Seeding skipped (DB_SEED=never)."
elif [ "$DB_SEED" = "always" ] || [ "$NEEDS_SEED" = "yes" ]; then
    echo "[INFO] Empty database — seeding (default login: admin@example.com / password)..."
    php artisan db:seed --force --no-interaction
else
    echo "[INFO] Database already has users — skipping seeding."
fi

# Cache for non-local environments
if [ "$APP_ENV" != "local" ] && [ "$APP_ENV" != "development" ]; then
    echo "[INFO] Caching config, routes and views..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Storage symlink
php artisan storage:link --force 2>/dev/null || true

# Fix permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "[INFO] Application is ready. Starting Apache..."
echo "======================================"

exec "$@"
