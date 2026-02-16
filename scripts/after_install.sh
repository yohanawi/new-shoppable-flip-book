#!/usr/bin/env bash
set -euo pipefail

log() {
  echo "[$(date -Is)] [after_install] $*"
}

detect_web_user() {
  if id -u www-data >/dev/null 2>&1; then
    echo "www-data:www-data"
    return
  fi
  if id -u nginx >/dev/null 2>&1; then
    echo "nginx:nginx"
    return
  fi
  if id -u apache >/dev/null 2>&1; then
    echo "apache:apache"
    return
  fi
  echo "root:root"
}

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
WEB_USER_GROUP="$(detect_web_user)"
WEB_USER="${WEB_USER_GROUP%%:*}"
WEB_GROUP="${WEB_USER_GROUP##*:}"

log "Using APP_DIR=$APP_DIR"
log "Detected WEB_USER=$WEB_USER WEB_GROUP=$WEB_GROUP"

cd "$APP_DIR"

if [[ ! -f ".env" && -f ".env.example" ]]; then
  log "Creating .env from .env.example (none found)"
  cp .env.example .env
fi

if command -v composer >/dev/null 2>&1; then
  log "Installing PHP dependencies (composer install)"
  composer install --no-interaction --prefer-dist --optimize-autoloader
else
  log "composer not found; skipping composer install"
fi

if command -v php >/dev/null 2>&1 && [[ -f artisan ]]; then
  if [[ -f .env ]]; then
    if ! php artisan key:status --no-interaction >/dev/null 2>&1; then
      log "Generating APP_KEY"
      php artisan key:generate --force --no-interaction
    fi

    log "Caching config/routes/views"
    php artisan config:cache --no-interaction || true
    php artisan route:cache --no-interaction || true
    php artisan view:cache --no-interaction || true

    log "Ensuring storage symlink"
    php artisan storage:link --no-interaction || true

    if [[ "${RUN_MIGRATIONS:-0}" == "1" ]]; then
      log "Running migrations (RUN_MIGRATIONS=1)"
      php artisan migrate --force --no-interaction
    else
      log "Skipping migrations (set RUN_MIGRATIONS=1 to enable)"
    fi
  else
    log "No .env found; skipping artisan caches and key generation"
  fi
else
  log "php/artisan not available; skipping artisan steps"
fi

if command -v npm >/dev/null 2>&1; then
  if [[ -f package.json ]]; then
    if [[ -f package-lock.json ]]; then
      log "Installing Node dependencies (npm ci)"
      npm ci --no-audit --no-fund
    else
      log "Installing Node dependencies (npm install)"
      npm install --no-audit --no-fund
    fi

    log "Building assets (npm run production)"
    npm run production
  else
    log "package.json missing; skipping Node steps"
  fi
else
  log "npm not found; skipping Node steps"
fi

log "Fixing writable permissions for storage and cache"
mkdir -p storage bootstrap/cache
chown -R "$WEB_USER:$WEB_GROUP" storage bootstrap/cache || true
chmod -R ug+rwX storage bootstrap/cache || true

log "AfterInstall completed"
