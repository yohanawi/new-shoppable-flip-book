#!/usr/bin/env bash
set -euo pipefail

log() {
  echo "[$(date -Is)] [start] $*"
}

has_systemctl() {
  command -v systemctl >/dev/null 2>&1
}

service_exists_systemctl() {
  local svc="$1"
  systemctl list-unit-files --type=service --no-pager 2>/dev/null | awk '{print $1}' | grep -Fxq "$svc"
}

restart_service() {
  local svc="$1"
  if has_systemctl && service_exists_systemctl "$svc"; then
    log "Restarting $svc via systemctl"
    systemctl restart "$svc"
    return 0
  fi

  if command -v service >/dev/null 2>&1; then
    if service "$svc" status >/dev/null 2>&1; then
      log "Restarting $svc via service"
      service "$svc" restart
      return 0
    fi
  fi

  return 1
}

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

log "Using APP_DIR=$APP_DIR"
cd "$APP_DIR"

if command -v php >/dev/null 2>&1 && [[ -f artisan ]] && [[ -f .env ]]; then
  log "Bringing application out of maintenance mode (if needed)"
  php artisan up --no-interaction || true
fi

# Restart PHP-FPM (try common service names)
PHPFPM_RESTARTED=0
for svc in php-fpm php8.3-fpm php8.2-fpm php8.1-fpm php8.0-fpm; do
  if restart_service "$svc"; then
    PHPFPM_RESTARTED=1
    break
  fi
done
if [[ "$PHPFPM_RESTARTED" == "0" ]]; then
  log "No php-fpm service restarted (may be running via Apache mod_php)"
fi

# Restart web server (nginx or apache)
if restart_service nginx; then
  :
elif restart_service apache2; then
  :
elif restart_service httpd; then
  :
else
  log "No web server service restarted (nginx/apache not detected)"
fi

log "ApplicationStart completed"
