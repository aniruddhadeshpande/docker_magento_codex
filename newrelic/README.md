# New Relic Observability

This directory contains optional New Relic configuration for the Magento Docker learning stack.

Nothing here is required for normal local development. The observability services only start with:

```bash
docker compose --profile observability up -d
```

Runtime credentials stay in `.env`, not in this directory. Use `NEW_RELIC_LICENSE_KEY`, `NEW_RELIC_ACCOUNT_ID`, `NEW_RELIC_REGION`, `NEW_RELIC_APP_NAME`, and `NEW_RELIC_ENVIRONMENT` from `.env.example`.

## Components

- PHP APM: `php-fpm` image includes the New Relic PHP extension and talks to `newrelic-php-daemon`.
- Infrastructure and logs: `newrelic-infra` reads Docker/container metadata and tails Docker JSON logs from the host mount.
- RabbitMQ and Nginx metrics: `newrelic-otel-collector` uses OpenTelemetry receivers.
- Redis metrics: New Relic infrastructure Redis integration monitors `redis:6379`.
- MySQL, OpenSearch, Varnish, and PHP-FPM internals: exporters expose Prometheus metrics scraped by the OTel collector.

## Guides

- `docs/observability/implementation-guide.md`
- `docs/observability/verification-guide.md`
- `docs/observability/troubleshooting.md`
- `docs/observability/security-considerations.md`
- `newrelic/queries/verification.nrql`
- `newrelic/dashboards/README.md`
- `newrelic/alerts/README.md`

## Privacy Defaults

Do not add customer identifiers, emails, session IDs, order IDs, cart IDs, cookies, authorization headers, payment data, raw addresses, or request bodies to logs, attributes, spans, or metrics labels.
