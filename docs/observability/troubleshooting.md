# Observability Troubleshooting

## PHP Extension Missing

Check:

```bash
docker compose exec -T php-fpm php -m
```

Fix:

```bash
docker compose build --no-cache php-fpm
docker compose up -d php-fpm
```

## No APM Data

Check `.env`:

```dotenv
NEW_RELIC_LICENSE_KEY=<license-key>
NEW_RELIC_ENABLED=true
NEW_RELIC_APP_NAME=docker-magento-learning
```

Then restart:

```bash
docker compose --profile observability up -d --build
docker compose logs --tail=100 php-fpm newrelic-php-daemon
```

## Infrastructure Agent Is Idle

This is expected when `NEW_RELIC_LICENSE_KEY` is empty:

```text
NEW_RELIC_LICENSE_KEY is empty; newrelic-infra is idle.
```

Set a real key and recreate the service.

## OTel 401 Or 403

The collector can run without a valid key, but New Relic rejects exports.

Check:

```bash
docker compose logs --tail=100 newrelic-otel-collector
```

Fix the license key, region, and OTLP endpoint.

## Nginx Status Returns 403 Or 502

Reload the Nginx containers after config changes:

```bash
docker compose restart nginx tls-proxy
```

Then check:

```bash
docker compose exec -T nginx wget -q -O - http://127.0.0.1/nginx_status
docker compose exec -T tls-proxy wget -q -O - --no-check-certificate https://127.0.0.1/nginx_status
```

## PHP-FPM Exporter Fails

Check PHP-FPM status configuration:

```bash
docker compose exec -T nginx wget -q -O - http://127.0.0.1/fpm-ping
docker compose logs --tail=100 php-fpm-exporter
```

If `fpm-ping` fails, rebuild `php-fpm` because the pool config is copied into
the image.

## MySQL Exporter Fails

Check:

```bash
docker compose logs --tail=100 mysql-exporter
docker compose exec -T nginx wget -q --spider http://mysql-exporter:9104/metrics
```

The Magento database user may not expose every metric. For production, create a
dedicated exporter user with least-privilege monitoring grants.

## OpenSearch Exporter Fails

Check:

```bash
docker compose logs --tail=100 opensearch-exporter
docker compose exec -T nginx wget -q --spider http://opensearch-exporter:9114/metrics
```

Confirm OpenSearch is healthy:

```bash
docker compose exec -T opensearch curl -fsS http://localhost:9200/_cluster/health
```

## Varnish Exporter Fails

Check:

```bash
docker compose logs --tail=100 varnish-exporter
docker compose exec -T varnish varnishstat -1 -j -n /var/lib/varnish/varnishd
```

The exporter depends on the shared `varnish_workdir` volume.

## Logs Missing

Check that the infrastructure agent has a license key and the host Docker log
driver writes JSON logs under `/var/lib/docker/containers`.

```bash
docker compose logs --tail=100 newrelic-infra
```
