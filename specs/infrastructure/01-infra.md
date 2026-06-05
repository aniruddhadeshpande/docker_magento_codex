# Infrastructure Spec

## Goal

Create the Docker infrastructure for a local Magento Open Source 2.4.9 learning stack.

The stack must expose the application at:

```text
https://magento.docker
```

Primary request flow:

```text
User -> TLS proxy :443 -> Varnish -> Nginx -> PHP-FPM -> MySQL
```

## Required Services

The `docker-compose.yml` file must define these services:

- `tls-proxy`
- `varnish`
- `nginx`
- `php-fpm`
- `mysql`
- `opensearch`
- `rabbitmq`
- `redis`

## Magento 2.4.9 Version Criteria

Use service versions aligned with Adobe Commerce / Magento Open Source 2.4.9 on-premises system requirements:

```text
PHP 8.5 FPM
MySQL 8.4
Nginx 1.30
Varnish 7
OpenSearch 3
RabbitMQ 4.2
Redis 6.2
Composer 2.9.3 or newer
```

Implementation should use exact major versions where Docker images are available. If an exact image tag cannot be used, document the reason before choosing a compatible alternative.

## Service Requirements

### tls-proxy

- Use an Nginx-based HTTPS reverse proxy.
- Bind host port `443` to container port `443`.
- Use local TLS certificate files for `magento.docker`.
- Forward requests to `varnish:80`.
- Set forwarding headers:
  - `Host`
  - `X-Real-IP`
  - `X-Forwarded-For`
  - `X-Forwarded-Proto`
- Use restart policy `unless-stopped`.
- Include a basic healthcheck.

Varnish does not terminate TLS, so TLS must be terminated before Varnish.

### varnish

- Use `varnish:7` as the HTTP caching layer.
- Use Magento-generated VCL for Varnish 7.
- Listen on internal port `80`.
- Use `varnish/default.vcl`.
- Forward cache misses and passed requests to `nginx:80`.
- Use restart policy `unless-stopped`.
- Include a basic healthcheck.

### nginx

- Use Nginx 1.30 or a documented compatible image.
- Serve Magento from `/var/www/html`.
- Use Magento's `pub` directory as the web root.
- Route PHP requests to `php-fpm:9000`.
- Use `nginx/default.conf`.
- Use restart policy `unless-stopped`.
- Include a basic healthcheck.
- See `08-nginx.md` for Nginx-specific requirements.

### php-fpm

- Build from `php/Dockerfile`.
- Use PHP 8.5 FPM for Magento Open Source 2.4.9.
- Install Composer 2.9.3 or newer.
- Install PHP extensions required by Magento, including at minimum:
  - `bcmath`
  - `ctype`
  - `curl`
  - `dom`
  - `fileinfo`
  - `filter`
  - `gd`
  - `hash`
  - `iconv`
  - `intl`
  - `json`
  - `mbstring`
  - `openssl`
  - `pdo`
  - `pdo_mysql`
  - `session`
  - `simplexml`
  - `soap`
  - `sockets`
  - `sodium`
  - `xmlwriter`
  - `xsl`
  - `zip`
- Mount `./magento` to `/var/www/html`.
- Read Magento, database, RabbitMQ, OpenSearch, and base URL settings from environment variables.
- Connect to Redis by Docker service name `redis` when Magento cache and sessions are configured for Redis.
- Use restart policy `unless-stopped`.

### mysql

- Use `mysql:8.4`.
- Store data in a persistent Docker volume.
- Do not initialize dummy Magento tables manually.
- Let Magento setup commands create and manage schema.
- Read database credentials from `.env`.
- Use restart policy `unless-stopped`.
- Include a healthcheck using `mysqladmin ping`.

### opensearch

- Use an OpenSearch 3 image compatible with Magento Open Source 2.4.9.
- Run in single-node development mode.
- Disable OpenSearch security for learning simplicity with Docker-supported environment flags.
- Store data in a persistent Docker volume.
- Use restart policy `unless-stopped`.
- Include a healthcheck for `http://opensearch:9200`.

### rabbitmq

- Use a RabbitMQ 4.2 management image compatible with Magento Open Source 2.4.9.
- Expose the management UI on host port `15672`.
- Store data in a persistent Docker volume.
- Read credentials from `.env`.
- Use restart policy `unless-stopped`.
- Include a healthcheck using `rabbitmq-diagnostics ping`.

### redis

- Use `redis:6.2-alpine`.
- Use Redis for Magento default cache and session storage.
- Do not use Redis for Magento full page cache; Varnish remains the full page cache layer.
- Do not expose Redis on a public host port by default.
- Store data in a persistent Docker volume.
- Join `learning_network`.
- Use restart policy `unless-stopped`.
- Include a healthcheck using `redis-cli ping`.
- See `09-redis.md` for Redis-specific requirements.

## Network

Define one Docker network:

```text
learning_network
```

All services must join this network and communicate by Docker service name.

## Volumes

Define these Docker volumes:

```text
mysql_data
opensearch_data
rabbitmq_data
redis_data
```

## Environment Configuration

Create a `.env` file with these defaults:

```env
APP_NAME=docker-magento-learning
APP_DOMAIN=magento.docker
HTTPS_PORT=443
MAGENTO_BASE_URL=https://magento.docker/
MAGENTO_BACKEND_FRONTNAME=admin
MAGENTO_ADMIN_USER=admin
MAGENTO_ADMIN_PASSWORD=Admin123!
MAGENTO_ADMIN_EMAIL=admin@example.test
MAGENTO_ADMIN_FIRSTNAME=Admin
MAGENTO_ADMIN_LASTNAME=User

MYSQL_DATABASE=magento
MYSQL_USER=magento
MYSQL_PASSWORD=magento_password
MYSQL_ROOT_PASSWORD=root_password

RABBITMQ_DEFAULT_USER=guest
RABBITMQ_DEFAULT_PASS=guest
RABBITMQ_MANAGEMENT_PORT=15672

OPENSEARCH_HOST=opensearch
OPENSEARCH_PORT=9200
OPENSEARCH_INDEX_PREFIX=magento2

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_CACHE_DB=0
REDIS_SESSION_DB=2
```

## Local Domain

The host machine must include:

```text
127.0.0.1 magento.docker
```

Document this in run instructions. Do not try to edit the host file automatically unless the user explicitly asks.

## TLS Certificate

The implementation must support local certificate files:

```text
certs/magento.docker.crt
certs/magento.docker.key
```

Prefer documenting `mkcert` for local trusted certificates. If `mkcert` is unavailable, document an `openssl` self-signed fallback.

## Acceptance Criteria

- `docker compose config` succeeds.
- All required services are present.
- All required volumes are present.
- All services are attached to `learning_network`.
- Public application traffic enters through `https://magento.docker` on port `443`.
- TLS terminates before Varnish.
- Varnish remains in the request path before Nginx.
- Redis is internal-only and available to PHP-FPM by Docker service name.
- Healthchecks and restart policies are configured.
- Service versions are aligned with Magento Open Source 2.4.9 requirements or documented exceptions.

## Acceptance Command

```bash
docker compose config
```
