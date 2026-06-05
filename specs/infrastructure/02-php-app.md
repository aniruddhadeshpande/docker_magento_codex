# PHP-FPM And Magento Runtime Spec

## Goal

Define the PHP-FPM runtime required to install and run Magento Open Source 2.4.9 inside Docker.

This spec does not create a custom PHP application. Magento Open Source is the application.

## Required Runtime

Use:

```text
PHP 8.5 FPM
Composer 2.9.3 or newer
```

The PHP container must be built from:

```text
php/Dockerfile
```

The Magento application directory must be mounted at:

```text
/var/www/html
```

Host path:

```text
./magento
```

## PHP Extensions

Install Magento-required PHP extensions, including at minimum:

```text
bcmath
ctype
curl
dom
fileinfo
filter
gd
hash
iconv
intl
json
mbstring
openssl
pdo
pdo_mysql
session
simplexml
soap
sockets
sodium
xmlwriter
xsl
zip
```

Use PHP configuration suitable for a local Magento learning install:

```text
memory_limit=2G
max_execution_time=1800
upload_max_filesize=64M
post_max_size=64M
realpath_cache_size=10M
realpath_cache_ttl=7200
```

## Composer And Authentication

Magento Open Source is installed through Composer.

The project may include:

```text
auth/auth.json.example
```

Do not commit real Adobe Marketplace credentials.

## Magento Install Command

The implementation must document a Magento setup command that reads service values from `.env`.

The command must configure:

- Base URL: `https://magento.docker/`
- Database host: `mysql`
- Search engine host: `opensearch`
- RabbitMQ host: `rabbitmq`
- Redis host: `redis`
- Web server root: Magento `pub`
- Backend front name from `MAGENTO_BACKEND_FRONTNAME`

## Redis Runtime Configuration

After Magento is installed, the implementation must document commands to configure Redis for:

- Magento default cache backend.
- Magento session storage.

Redis must not be configured as Magento full page cache while Varnish is enabled.

Use Docker service discovery:

```text
redis:6379
```

Recommended Magento CLI commands:

```bash
docker compose exec --user www-data php-fpm php bin/magento setup:config:set \
  --cache-backend=redis \
  --cache-backend-redis-server=redis \
  --cache-backend-redis-port=6379 \
  --cache-backend-redis-db=0

docker compose exec --user www-data php-fpm php bin/magento setup:config:set \
  --session-save=redis \
  --session-save-redis-host=redis \
  --session-save-redis-port=6379 \
  --session-save-redis-db=2

docker compose exec --user www-data php-fpm php bin/magento cache:flush
```

## Ownership And Permissions

The PHP-FPM user must be able to read and write Magento runtime paths, including:

```text
var/
generated/
pub/static/
pub/media/
app/etc/
```

## Acceptance Criteria

- PHP-FPM starts successfully.
- `php -v` shows PHP 8.5 inside the container.
- `composer --version` shows Composer 2.9.3 or newer.
- Required Magento PHP extensions are installed.
- Magento files can be installed into `./magento`.
- Magento CLI can run from `/var/www/html/bin/magento`.
- PHP-FPM can connect to MySQL, OpenSearch, RabbitMQ, and Redis by Docker service name.
