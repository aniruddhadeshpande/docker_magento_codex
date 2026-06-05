#!/usr/bin/env bash
set -Eeuo pipefail

COMPOSE=(docker compose)
PHP_SERVICE="${PHP_SERVICE:-php-fpm}"
MAGENTO_ROOT="${MAGENTO_ROOT:-/var/www/html}"
MAGENTO_USER="${MAGENTO_USER:-www-data}"
MAGENTO_GROUP="${MAGENTO_GROUP:-www-data}"
STATIC_LOCALE="${STATIC_LOCALE:-en_US}"

START_TIME="$(date +%s)"
CURRENT_STEP="initialization"
MAINTENANCE_ENABLED=0

if [[ -t 1 && -z "${NO_COLOR:-}" ]]; then
    BOLD=$'\033[1m'
    RED=$'\033[31m'
    GREEN=$'\033[32m'
    YELLOW=$'\033[33m'
    CYAN=$'\033[36m'
    RESET=$'\033[0m'
else
    BOLD=""
    RED=""
    GREEN=""
    YELLOW=""
    CYAN=""
    RESET=""
fi

elapsed_time() {
    local now elapsed hours minutes seconds

    now="$(date +%s)"
    elapsed=$((now - START_TIME))
    hours=$((elapsed / 3600))
    minutes=$(((elapsed % 3600) / 60))
    seconds=$((elapsed % 60))

    printf "%02dh %02dm %02ds" "$hours" "$minutes" "$seconds"
}

on_error() {
    local exit_code="$1"

    trap - ERR

    echo
    echo "${RED}${BOLD}FAILED:${RESET} ${CURRENT_STEP}"
    echo "${RED}Exit code:${RESET} ${exit_code}"
    echo "${CYAN}Elapsed time:${RESET} $(elapsed_time)"

    if [[ "$MAINTENANCE_ENABLED" -eq 1 ]]; then
        echo "${YELLOW}Maintenance mode was enabled and has been left ON intentionally.${RESET}"
        echo "After fixing the issue, run:"
        echo "  ${CYAN}docker compose exec --user ${MAGENTO_USER} ${PHP_SERVICE} php bin/magento maintenance:disable${RESET}"
    fi

    exit "$exit_code"
}

trap 'on_error $?' ERR

step() {
    CURRENT_STEP="$1"
    shift

    echo
    echo "${BOLD}${CYAN}==>${RESET} ${BOLD}${CURRENT_STEP}${RESET}"
    "$@"
    echo "${GREEN}--> Completed:${RESET} ${CURRENT_STEP}"
}

run_magento() {
    "${COMPOSE[@]}" exec -T \
        --user "$MAGENTO_USER" \
        --workdir "$MAGENTO_ROOT" \
        "$PHP_SERVICE" \
        php bin/magento --no-interaction "$@"
}

run_root() {
    "${COMPOSE[@]}" exec -T \
        --workdir "$MAGENTO_ROOT" \
        "$PHP_SERVICE" \
        "$@"
}

require_project_root() {
    if [[ ! -f "docker-compose.yml" || ! -d "magento" ]]; then
        echo "${RED}Run this script from the project root.${RESET}"
        exit 1
    fi
}

load_env_file() {
    if [[ -f ".env" ]]; then
        set -a
        # shellcheck disable=SC1091
        source ".env"
        set +a
    fi
}

require_docker_compose() {
    command -v docker >/dev/null 2>&1
    "${COMPOSE[@]}" version >/dev/null
}

require_php_fpm_running() {
    local running_services

    running_services="$("${COMPOSE[@]}" ps --services --filter status=running)"

    if ! grep -qx "$PHP_SERVICE" <<< "$running_services"; then
        echo "${RED}Docker Compose service '${PHP_SERVICE}' is not running.${RESET}"
        echo "Start the stack first:"
        echo "  ${CYAN}docker compose up -d${RESET}"
        exit 1
    fi
}

repair_permissions() {
    run_root sh -lc "
        chown -R ${MAGENTO_USER}:${MAGENTO_GROUP} var generated pub/static pub/media app/etc
        find var generated pub/static pub/media app/etc -type d -exec chmod 775 {} +
        find var generated pub/static pub/media app/etc -type f -exec chmod 664 {} +
    "
}

configure_cache_layers() {
    local redis_host redis_port redis_cache_db redis_session_db redis_serializer
    local http_cache_hosts varnish_backend_host varnish_backend_port varnish_purge_access_list

    redis_host="${REDIS_HOST:-redis}"
    redis_port="${REDIS_PORT:-6379}"
    redis_cache_db="${REDIS_CACHE_DB:-0}"
    redis_session_db="${REDIS_SESSION_DB:-2}"
    redis_serializer="${REDIS_CACHE_SERIALIZER:-php}"
    http_cache_hosts="${HTTP_CACHE_HOSTS:-varnish:80}"
    varnish_backend_host="${VARNISH_BACKEND_HOST:-nginx}"
    varnish_backend_port="${VARNISH_BACKEND_PORT:-80}"
    varnish_purge_access_list="${VARNISH_PURGE_ACCESS_LIST:-localhost,172.23.0.0/16}"

    run_magento setup:config:set \
        --cache-backend=redis \
        --cache-backend-redis-server="$redis_host" \
        --cache-backend-redis-port="$redis_port" \
        --cache-backend-redis-db="$redis_cache_db" \
        --cache-backend-redis-serializer="$redis_serializer"

    run_magento setup:config:set \
        --session-save=redis \
        --session-save-redis-host="$redis_host" \
        --session-save-redis-port="$redis_port" \
        --session-save-redis-db="$redis_session_db"

    run_magento config:set --lock-env system/full_page_cache/caching_application 2
    run_magento config:set --lock-env system/full_page_cache/varnish/backend_host "$varnish_backend_host"
    run_magento config:set --lock-env system/full_page_cache/varnish/backend_port "$varnish_backend_port"
    run_magento config:set --lock-env system/full_page_cache/varnish/access_list "$varnish_purge_access_list"
    run_magento setup:config:set --http-cache-hosts="$http_cache_hosts"
}

echo "${BOLD}${CYAN}Magento local deployment started${RESET}"
echo "${CYAN}Service:${RESET} ${PHP_SERVICE}"
echo "${CYAN}Magento root:${RESET} ${MAGENTO_ROOT}"
echo "${CYAN}Runtime user/group:${RESET} ${MAGENTO_USER}:${MAGENTO_GROUP}"
echo "${CYAN}Static locale:${RESET} ${STATIC_LOCALE}"

step "Check project root" require_project_root
step "Load local environment defaults" load_env_file
step "Check Docker Compose" require_docker_compose
step "Check PHP-FPM service is running" require_php_fpm_running

step "Repair Magento ownership and permissions before deployment" repair_permissions
step "Enable maintenance mode" run_magento maintenance:enable
MAINTENANCE_ENABLED=1

step "Run setup upgrade" run_magento setup:upgrade
step "Configure L1/L2/L3 cache layers" configure_cache_layers
step "Compile dependency injection" run_magento setup:di:compile
step "Deploy static content" run_magento setup:static-content:deploy -f "$STATIC_LOCALE"
step "Repair Magento ownership and permissions after deployment" repair_permissions
step "Flush Magento cache" run_magento cache:flush

step "Disable maintenance mode" run_magento maintenance:disable
MAINTENANCE_ENABLED=0

echo
echo "${GREEN}${BOLD}Magento local deployment completed successfully${RESET}"
echo "${CYAN}Total execution time:${RESET} $(elapsed_time)"
