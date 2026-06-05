# Redis Spec

## Goal

Define Redis as the Magento cache and session storage service for the Magento Open Source 2.4.9 Docker learning stack.

Redis is an internal infrastructure service. It is not exposed publicly and is reached by Magento through Docker service discovery.

References:

- Adobe Redis cache documentation: <https://experienceleague.adobe.com/en/docs/commerce-operations/configuration-guide/cache/redis/redis-pg-cache>
- Adobe Redis session documentation: <https://experienceleague.adobe.com/en/docs/commerce-operations/configuration-guide/cache/redis/redis-session>
- Adobe system requirements: <https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/system-requirements>

## Docker Service

Define a `redis` service in `docker-compose.yml`.

Use:

```text
redis:6.2-alpine
```

Recommended service shape:

```yaml
redis:
  image: redis:6.2-alpine
  command: ["redis-server", "--appendonly", "yes"]
  volumes:
    - redis_data:/data
  networks:
    - learning_network
  restart: unless-stopped
  healthcheck:
    test: ["CMD", "redis-cli", "ping"]
    interval: 30s
    timeout: 10s
    retries: 5
```

Do not expose a Redis host port by default. Magento and PHP-FPM must connect through:

```text
redis:6379
```

## Docker Volume

Define a persistent Redis volume:

```text
redis_data
```

This volume stores Redis append-only persistence data for the learning stack.

## Environment Variables

Add Redis settings to `.env`:

```env
REDIS_HOST=redis
REDIS_PORT=6379
REDIS_CACHE_DB=0
REDIS_SESSION_DB=2
```

Do not configure a Redis password for this local-only learning stack unless a later security spec requires it.

## Magento Configuration

Use Redis for:

- Magento default cache backend.
- Magento session storage.

Do not use Redis for Magento full page cache while Varnish is enabled.

Configure default cache:

```bash
docker compose exec --user www-data php-fpm php bin/magento setup:config:set \
  --cache-backend=redis \
  --cache-backend-redis-server=redis \
  --cache-backend-redis-port=6379 \
  --cache-backend-redis-db=0
```

Configure sessions:

```bash
docker compose exec --user www-data php-fpm php bin/magento setup:config:set \
  --session-save=redis \
  --session-save-redis-host=redis \
  --session-save-redis-port=6379 \
  --session-save-redis-db=2
```

Flush Magento cache after configuration:

```bash
docker compose exec --user www-data php-fpm php bin/magento cache:flush
```

## Learning Notes

- Redis DB `0` is reserved for Magento default cache.
- Redis DB `2` is reserved for Magento sessions.
- Varnish remains the full page cache layer in front of Nginx.
- Redis improves backend cache/session behavior; it does not replace the HTTP cache layer in this stack.

## Acceptance Criteria

- `docker compose config` succeeds.
- `redis` service is present.
- `redis_data` volume is present.
- Redis joins `learning_network`.
- Redis has no public host port by default.
- Redis healthcheck uses `redis-cli ping`.
- `docker compose exec redis redis-cli ping` returns `PONG`.
- Magento `app/etc/env.php` contains Redis configuration for default cache.
- Magento `app/etc/env.php` contains Redis configuration for sessions.
- Magento full page cache remains configured for Varnish.
- Storefront still loads at `https://magento.docker`.
