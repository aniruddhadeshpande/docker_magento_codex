# New Relic Implementation Guide

## Default Startup

Normal Magento startup remains unchanged:

```bash
docker compose up -d
```

The New Relic services are optional and use the `observability` profile:

```bash
docker compose --profile observability up -d --build
```

## Environment

Copy the New Relic placeholders from `.env.example` into `.env` and set real
values locally:

```dotenv
NEW_RELIC_LICENSE_KEY=
NEW_RELIC_ACCOUNT_ID=
NEW_RELIC_API_KEY=
NEW_RELIC_REGION=US
NEW_RELIC_OTLP_ENDPOINT=https://otlp.nr-data.net:4318
NEW_RELIC_APP_NAME=docker-magento-learning
NEW_RELIC_ENVIRONMENT=local
NEW_RELIC_LOG_LEVEL=info
NEW_RELIC_ENABLED=false
NEW_RELIC_DISTRIBUTED_TRACING_ENABLED=true
NEW_RELIC_BROWSER_MONITORING_AUTO_INSTRUMENT=true
```

Set `NEW_RELIC_ENABLED=true` when you want the PHP agent to send APM data.

For EU accounts, set:

```dotenv
NEW_RELIC_REGION=EU
NEW_RELIC_OTLP_ENDPOINT=https://otlp.eu01.nr-data.net:4318
```

## Implemented Components

| Component | Service/config |
| --- | --- |
| Magento PHP APM | `php/Dockerfile`, `php/conf.d/newrelic.ini` |
| PHP daemon | `newrelic-php-daemon` |
| Docker/container metrics and logs | `newrelic-infra`, `newrelic/infrastructure/*` |
| RabbitMQ metrics | `newrelic-otel-collector` RabbitMQ receiver |
| Nginx/TLS metrics | `nginx/default.conf`, `tls/default.conf`, OTel NGINX receivers |
| Redis metrics | `newrelic/infrastructure/integrations/redis-config.yml` |
| PHP-FPM metrics | `php/php-fpm.d/zz-observability.conf`, `php-fpm-exporter` |
| MySQL metrics | `mysql-exporter` |
| OpenSearch metrics | `opensearch-exporter` |
| Varnish metrics | `varnish-exporter` local exporter |

## No-License Behavior

Without `NEW_RELIC_LICENSE_KEY`:

- `newrelic-infra` starts and idles with a clear log message.
- `newrelic-otel-collector` can start, but New Relic rejects exports with
  401/403 responses.
- Exporter endpoints still work locally for smoke testing.
- The PHP extension is installed but should stay disabled unless
  `NEW_RELIC_ENABLED=true`.

## Local Commands

```bash
docker compose config --services
docker compose --profile observability config --services
docker compose --profile observability up -d --build
docker compose exec -T php-fpm php -m
docker compose ps
```

Keep real New Relic credentials out of Git and out of copied command output.
