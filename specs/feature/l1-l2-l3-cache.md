# Magento L1/L2/L3 Cache Implementation

## Summary

Implement the cache layering as:

- L1: Magento/PHP local cache layer: generated code, static assets, Magento cache types, PHP OPcache, and realpath cache.
- L2: Redis application layer: Magento default cache in Redis DB `0`, sessions in Redis DB `2`.
- L3: Varnish HTTP full-page cache: TLS proxy -> Varnish -> Nginx -> PHP-FPM.

The current stack includes Redis, Varnish, OPcache, and Magento full-page cache configured as Varnish. The deployment script now makes this setup repeatable, configures Varnish purge hosts, and uses the PHP serializer supported by the current PHP image.

## Current State

- `docker-compose.yml` already defines `redis`, `varnish`, `nginx`, `tls-proxy`, and `php-fpm`.
- `magento/app/etc/env.php` uses Redis for the default Magento cache in DB `0`.
- `magento/app/etc/env.php` uses Redis for sessions in DB `2`.
- Magento reports `system/full_page_cache/caching_application = 2`, meaning Varnish is selected.
- `php/conf.d/magento.ini` already enables OPcache and realpath cache.
- `env.php` uses the `php` serializer for both default cache and page cache metadata.
- `env.php` contains `http_cache_hosts` with `varnish:80`, so Magento cache flushes can target Varnish.

## Planned Implementation

`scripts/magento-deploy.sh` includes a cache configuration step after `setup:upgrade` and before DI compile.

Configure Redis default cache:

```bash
php bin/magento setup:config:set \
  --cache-backend=redis \
  --cache-backend-redis-server="${REDIS_HOST:-redis}" \
  --cache-backend-redis-port="${REDIS_PORT:-6379}" \
  --cache-backend-redis-db="${REDIS_CACHE_DB:-0}" \
  --cache-backend-redis-serializer="${REDIS_CACHE_SERIALIZER:-php}"
```

Configure Redis sessions:

```bash
php bin/magento setup:config:set \
  --session-save=redis \
  --session-save-redis-host="${REDIS_HOST:-redis}" \
  --session-save-redis-port="${REDIS_PORT:-6379}" \
  --session-save-redis-db="${REDIS_SESSION_DB:-2}"
```

Configure Varnish full-page cache:

```bash
php bin/magento config:set --lock-env system/full_page_cache/caching_application 2
php bin/magento config:set --lock-env system/full_page_cache/varnish/backend_host nginx
php bin/magento config:set --lock-env system/full_page_cache/varnish/backend_port 80
php bin/magento config:set --lock-env system/full_page_cache/varnish/access_list localhost,172.23.0.0/16
php bin/magento setup:config:set --http-cache-hosts=varnish:80
```

Configured in `.env`:

```env
REDIS_CACHE_SERIALIZER=php
HTTP_CACHE_HOSTS=varnish:80
VARNISH_BACKEND_HOST=nginx
VARNISH_BACKEND_PORT=80
VARNISH_PURGE_ACCESS_LIST=localhost,172.23.0.0/16
```

Keep Redis out of Magento full-page cache while Varnish is enabled.

## Verification

Run:

```bash
docker compose config
./scripts/magento-deploy.sh
docker compose exec redis redis-cli ping
docker compose exec --user www-data php-fpm php bin/magento cache:status
docker compose exec --user www-data php-fpm php bin/magento config:show system/full_page_cache/caching_application
docker compose exec --user www-data php-fpm php -r 'print_r(include "app/etc/env.php");'
curl -k -I https://magento.docker/
curl -k -I https://magento.docker/
```

Expected results:

- Redis returns `PONG`.
- Magento cache types are enabled.
- Full-page cache application returns `2`.
- `env.php` contains Redis default cache DB `0`.
- `env.php` contains Redis session DB `2`.
- `env.php` contains `http_cache_hosts` with `varnish:80`.
- Repeated storefront requests show Varnish cache headers such as `X-Cache`.

## Assumptions

- L1/L2/L3 means Magento/PHP local cache, Redis backend/session cache, and Varnish full-page HTTP cache.
- This remains a local learning stack.
- Redis stays internal and is not exposed publicly.
- Redis full-page cache is intentionally not used.
- The default Redis serializer is `php` unless the PHP image is later extended with `igbinary`.
