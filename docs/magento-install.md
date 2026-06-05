# Magento Installation Notes

These commands assume the Docker infrastructure is already built and healthy.

## 1. Local Domain

Add this host mapping on your machine:

```text
127.0.0.1 magento.docker
```

## 2. TLS Certificate

Preferred local certificate flow:

```bash
mkcert magento.docker
mv magento.docker.pem certs/magento.docker.crt
mv magento.docker-key.pem certs/magento.docker.key
```

Fallback self-signed certificate:

```bash
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout certs/magento.docker.key \
  -out certs/magento.docker.crt \
  -subj "/CN=magento.docker" \
  -addext "subjectAltName=DNS:magento.docker"
```

## 3. Composer Authentication

Copy the example and add Adobe Marketplace keys:

```bash
cp auth/auth.json.example auth/auth.json
```

Do not commit real credentials.

## 4. Start Infrastructure

```bash
docker compose up -d --build
```

## 5. Install Magento Open Source 2.4.9

Run Composer inside the PHP-FPM container:

```bash
docker compose exec php-fpm composer create-project \
  --repository-url=https://repo.magento.com/ \
  magento/project-community-edition=2.4.9 .
```

Then run setup:

```bash
docker compose exec php-fpm php bin/magento setup:install \
  --base-url="${MAGENTO_BASE_URL}" \
  --db-host=mysql \
  --db-name="${MYSQL_DATABASE}" \
  --db-user="${MYSQL_USER}" \
  --db-password="${MYSQL_PASSWORD}" \
  --backend-frontname="${MAGENTO_BACKEND_FRONTNAME}" \
  --admin-firstname="${MAGENTO_ADMIN_FIRSTNAME}" \
  --admin-lastname="${MAGENTO_ADMIN_LASTNAME}" \
  --admin-email="${MAGENTO_ADMIN_EMAIL}" \
  --admin-user="${MAGENTO_ADMIN_USER}" \
  --admin-password="${MAGENTO_ADMIN_PASSWORD}" \
  --language=en_US \
  --currency=USD \
  --timezone=UTC \
  --use-rewrites=1 \
  --search-engine=opensearch \
  --opensearch-host=opensearch \
  --opensearch-port=9200 \
  --opensearch-index-prefix="${OPENSEARCH_INDEX_PREFIX}" \
  --amqp-host=rabbitmq \
  --amqp-port=5672 \
  --amqp-user="${RABBITMQ_DEFAULT_USER}" \
  --amqp-password="${RABBITMQ_DEFAULT_PASS}" \
  --amqp-virtualhost=/
```

## 6. Verify Magento

```bash
docker compose exec php-fpm php bin/magento --version
docker compose exec php-fpm php bin/magento indexer:reindex
docker compose exec php-fpm php bin/magento cache:status
```

Open:

```text
https://magento.docker
```

