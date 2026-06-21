# New Relic Verification Guide

## Local Verification

Validate default Compose still excludes optional services:

```bash
docker compose config --services
```

Validate the observability profile:

```bash
docker compose --profile observability config --services
docker compose --profile observability up -d --build
docker compose ps
```

Verify the PHP agent extension:

```bash
docker compose exec -T php-fpm php -m
```

The output should include `newrelic`.

Verify internal status endpoints:

```bash
docker compose exec -T nginx wget -q -O - http://127.0.0.1/nginx_status
docker compose exec -T nginx wget -q -O - http://127.0.0.1/fpm-ping
docker compose exec -T tls-proxy wget -q -O - --no-check-certificate https://127.0.0.1/nginx_status
```

Verify exporter endpoints:

```bash
docker compose exec -T nginx wget -q --spider http://php-fpm-exporter:9253/metrics
docker compose exec -T nginx wget -q --spider http://mysql-exporter:9104/metrics
docker compose exec -T nginx wget -q --spider http://opensearch-exporter:9114/metrics
docker compose exec -T nginx wget -q --spider http://varnish-exporter:9131/metrics
```

Verify the OTel collector health endpoint:

```bash
docker compose exec -T nginx wget -q -O - http://newrelic-otel-collector:13133/
```

## Magento Checks

```bash
docker compose exec -T php-fpm php bin/magento module:status Training_StockNotifyQueue
docker compose exec -T php-fpm php bin/magento list training
docker compose exec -T php-fpm php bin/magento queue:consumers:list
```

## New Relic Verification

After setting a valid license key and enabling PHP APM:

```dotenv
NEW_RELIC_LICENSE_KEY=<license-key>
NEW_RELIC_ENABLED=true
```

Restart:

```bash
docker compose --profile observability up -d --build
```

Then generate traffic:

```bash
curl -k https://magento.docker/healthz
curl -k https://magento.docker/
docker compose exec -T php-fpm php bin/magento cache:status
```

Use `newrelic/queries/verification.nrql` to confirm APM transactions, errors,
container metrics, OTel metrics, and logs.

## Controlled Failure Checks

- Stop RabbitMQ and confirm queue/consumer alerts would fire.
- Request a missing URL and confirm Nginx/Magento status-code visibility.
- Temporarily stop OpenSearch and confirm exporter/collector scrape failures.
- Set `NEW_RELIC_LICENSE_KEY` to an invalid value and confirm New Relic returns
  401/403 without exposing the key in logs.

Restore the stack after each check:

```bash
docker compose --profile observability up -d
```
