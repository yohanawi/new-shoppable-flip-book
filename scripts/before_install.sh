#!/usr/bin/env bash
set -euo pipefail

log() {
  echo "[$(date -Is)] [before_install] $*"
}

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

log "Using APP_DIR=$APP_DIR"

if [[ -f "$APP_DIR/artisan" ]]; then
  cd "$APP_DIR"

  if command -v php >/dev/null 2>&1; then
    if [[ -f ".env" ]]; then
      log "Putting application into maintenance mode (if already deployed)"
      php artisan down --no-interaction || true
    else
      log "No .env found; skipping maintenance mode"
    fi
  else
    log "php not found; skipping artisan maintenance mode"
  fi
else
  log "artisan not found; skipping maintenance mode"
fi

log "BeforeInstall completed"
