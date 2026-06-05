# Verification Spec

## Goal

Define the final verification checklist for the Magento Open Source 2.4.9 Docker learning project.

This spec confirms that infrastructure, Magento, MySQL, RabbitMQ, OpenSearch, Redis, Nginx, Varnish, and TLS routing work together.

## Local Prerequisites

The host machine must resolve:

```text
127.0.0.1 magento.docker
```

Local TLS certificate files must exist:

```text
certs/magento.docker.crt
certs/magento.docker.key
```

Magento Marketplace credentials must be available only when Composer installation requires them. Do not commit real credentials.

## Startup Commands

Build and start the stack:

```bash
docker compose up -d --build
```

Check service status:

```bash
docker compose ps
```

Validate Compose configuration:

```bash
docker compose config
```

## Browser URLs

Magento storefront:

```text
https://magento.docker
```

RabbitMQ management UI:

```text
http://localhost:15672
```

OpenSearch:

```text
http://localhost:9200
```

## Logs

Useful log commands:

```bash
docker compose logs php-fpm
docker compose logs varnish
docker compose logs nginx
docker compose logs mysql
docker compose logs rabbitmq
docker compose logs opensearch
docker compose logs redis
```

## Magento Checks

Run Magento CLI checks from the PHP-FPM container:

```bash
docker compose exec php-fpm php -v
docker compose exec php-fpm composer --version
docker compose exec php-fpm php bin/magento --version
docker compose exec php-fpm php bin/magento indexer:reindex
docker compose exec php-fpm php bin/magento cache:status
docker compose exec --user www-data php-fpm php bin/magento config:show system/full_page_cache/caching_application
docker compose exec --user www-data php-fpm php -r 'print_r(include "app/etc/env.php");'
```

## Redis Checks

Run Redis checks from the Redis container:

```bash
docker compose ps redis
docker compose exec redis redis-cli ping
docker compose exec redis redis-cli -n 0 DBSIZE
docker compose exec redis redis-cli -n 2 DBSIZE
```

## Functional Checklist

- All containers start.
- Healthchecks become healthy where configured.
- `https://magento.docker` loads the Magento storefront.
- The request path includes TLS proxy, Varnish, Nginx, and PHP-FPM.
- Nginx serves Magento from the `pub` directory.
- Magento setup creates the database schema.
- MySQL data persists after:

```bash
docker compose down
docker compose up -d
```

- MySQL data is removed after:

```bash
docker compose down -v
```

- RabbitMQ management UI is reachable.
- Magento can connect to RabbitMQ through host `rabbitmq`.
- OpenSearch health endpoint responds.
- Magento indexing can use OpenSearch through host `opensearch`.
- Redis container is healthy.
- Magento default cache is configured to use Redis DB `0`.
- Magento sessions are configured to use Redis DB `2`.
- Redis is not exposed on a public host port by default.
- Varnish remains in the request flow.
- Varnish does not cache unsafe write requests or Magento session-sensitive routes.

## Shutdown Commands

Stop containers while keeping volumes:

```bash
docker compose down
```

Stop containers and remove volumes:

```bash
docker compose down -v
```

## Final Acceptance

The project is accepted when:

- `docker compose config` succeeds.
- `docker compose up -d --build` starts the full stack.
- All expected URLs are reachable.
- Magento Open Source 2.4.9 is installed or installation steps are documented and runnable.
- Magento storefront loads at `https://magento.docker`.
- Magento database data persists through normal restarts.
- RabbitMQ is configured for Magento message queues.
- OpenSearch is configured for Magento catalog search.
- Redis is configured for Magento default cache and sessions.
- Varnish remains configured as Magento full page cache.
- Varnish is active between the TLS proxy and Nginx.
