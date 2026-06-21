# Proposed New Relic Observability Architecture

## Summary

New Relic observability will be optional for local development. The normal
Magento command remains:

```bash
docker compose up -d
```

Observability components will be started only when the `observability` Compose
profile is used:

```bash
docker compose --profile observability up -d
```

The first implementation target is useful local learning and troubleshooting,
not production hardening. Production guidance is documented separately and must
not be silently applied to this local stack.

## Component Mapping

| Project component | New Relic component | Where it runs | Telemetry sent |
| --- | --- | --- | --- |
| Magento web, API, GraphQL, admin, CLI, cron, queue consumers | New Relic PHP APM agent `>= 12.4.0.29` | Inside `php-fpm` image | Transactions, errors, traces, datastore/external segments, custom attributes |
| PHP agent daemon | `newrelic/php-daemon` | Separate `newrelic-php-daemon` service | Receives PHP agent data and sends it to New Relic |
| Browser activity | New Relic Browser through PHP agent auto-instrumentation | Injected into Magento responses when enabled | Page load, Ajax, browser errors, frontend/backend correlation |
| Docker containers | New Relic infrastructure agent Docker monitoring | `newrelic-infra` service | CPU, memory, network, block IO, restarts, container metadata |
| Host | New Relic infrastructure agent | `newrelic-infra` service with host mounts | CPU, memory, disk, network, process and host metadata |
| Logs | Infrastructure agent log forwarding / Fluent Bit | `newrelic-infra` service | Magento, Nginx, TLS proxy, PHP-FPM, Varnish, RabbitMQ, Redis, MySQL, OpenSearch, agent logs |
| RabbitMQ | OpenTelemetry Collector RabbitMQ receiver | `newrelic-otel-collector` service | Queue, exchange, node, connection, consumer, and alarm metrics |
| Nginx and TLS proxy | OpenTelemetry Collector NGINX receiver | `newrelic-otel-collector` service | Connection/request metrics from local status endpoints |
| Redis | New Relic Redis on-host integration | `newrelic-infra` service | Redis metrics, keyspace metrics, inventory where safe |
| MySQL | MySQL exporter scraped by OpenMetrics | `mysql-exporter` plus `newrelic-otel-collector` or infrastructure OpenMetrics | Connections, throughput, command stats, InnoDB, locks, replication fields when present |
| OpenSearch | OpenSearch exporter scraped by OpenMetrics | `opensearch-exporter` plus collector/OpenMetrics | Cluster health, node, JVM, index, shard, rejection, disk metrics |
| Varnish | Varnish exporter scraped by OpenMetrics | `varnish-exporter` plus collector/OpenMetrics | Cache hit ratio, backend health/failures, storage, client/backend request counters |
| PHP-FPM internals | PHP-FPM exporter scraped by OpenMetrics | `php-fpm-exporter` plus collector/OpenMetrics | Active/idle workers, listen queue, accepted connections, max children reached |
| Magento cron/consumers | PHP APM plus optional custom Magento module | `php-fpm` | Background transactions, safe event attributes, cron/consumer health events |

## Telemetry Flow

```text
Magento PHP agent
  -> newrelic-php-daemon
  -> New Relic collector endpoint

newrelic-infra
  -> New Relic infrastructure/log endpoints

newrelic-otel-collector
  -> New Relic OTLP endpoint

exporters / service status APIs
  -> OpenTelemetry collector or infrastructure OpenMetrics integration
  -> New Relic
```

Region handling:

| `NEW_RELIC_REGION` | Endpoint behavior |
| --- | --- |
| `US` or empty | Use New Relic US endpoints |
| `EU` | Use New Relic EU endpoints |

## Required Docker Access

| Component | Required access | Why |
| --- | --- | --- |
| `newrelic-infra` | `/var/run/docker.sock:ro` | Container inventory and metrics |
| `newrelic-infra` | Host proc/sys/fs mounts read-only | Host metrics |
| `newrelic-infra` | Magento and service log files read-only where mounted | Log forwarding |
| `newrelic-otel-collector` | Internal network access to service APIs/exporters | Metrics collection |
| Exporters | Internal network access to their target service | Service-specific metrics |
| PHP agent | Runtime environment variables and daemon hostname | APM data forwarding |

The Docker socket is sensitive. It should be read-only in this local stack, and
production deployments should use the least privileged deployment model
available for the target platform.

## Secret Handling

Required runtime values:

```dotenv
NEW_RELIC_LICENSE_KEY=
NEW_RELIC_ACCOUNT_ID=
NEW_RELIC_API_KEY=
NEW_RELIC_REGION=US
NEW_RELIC_APP_NAME=docker-magento-learning
NEW_RELIC_ENVIRONMENT=local
NEW_RELIC_LOG_LEVEL=info
```

Rules:

- Keep real values in `.env` or a local secret store, never in Git.
- Do not copy credentials into Docker image layers.
- Do not print license keys or API keys in scripts.
- Use the license key for agents and ingest.
- Use the API key/account ID only for optional dashboard or alert API automation.
- Prefer placeholders in documentation and examples.

## Privacy Controls

Default collection must avoid:

- Passwords, cookies, session IDs, form keys, auth headers, and bearer tokens.
- Payment data, raw request bodies, and checkout payloads.
- Customer emails, full addresses, phone numbers, order IDs, cart IDs, customer
  IDs, and message IDs.
- High-cardinality URLs or arbitrary query strings.

Allowed low-cardinality attributes:

- `environment`
- `deployment.version`
- `store.code`
- `website.code`
- `area.code`
- `request.type`
- `graphql.operation.name`
- `rest.route`
- `queue.topic`
- `queue.consumer`
- `cron.job.code`

Browser session replay and browser logs should stay disabled by default for this
learning stack unless explicitly enabled and reviewed.

## Expected Overhead

| Area | Expected local overhead | Control |
| --- | --- | --- |
| PHP APM | Low to moderate per request | Keep default sampling, avoid excessive custom spans |
| Browser monitoring | Low frontend payload overhead | Disable session replay by default |
| Infrastructure agent | Low host/container overhead | Run only in observability profile |
| OpenTelemetry collector | Low to moderate depending on scrape interval | Use 30s-60s scrape intervals locally |
| Exporters | Low per service | Avoid overly frequent scraping |
| Logs | Potentially high | Filter debug logs and avoid duplicate collection |

## Development Versus Production

Local development:

- Observability is optional.
- New Relic services use the `observability` profile.
- Debug logs are not forwarded by default.
- Thresholds are starting points, not production SLOs.
- Data volume is controlled by conservative scrape intervals and log filters.

Production recommendations:

- Use platform-native deployment patterns rather than copying the local Docker
  profile directly.
- Store secrets in a secret manager.
- Define retention, ingest budgets, and drop rules.
- Tune alert thresholds after collecting a real baseline.
- Review browser privacy settings before enabling session replay.
- Restrict access to Docker socket or use a safer container monitoring model.
- Pin and update agents through a planned maintenance process.

## Compatibility Notes

- Current New Relic PHP documentation lists PHP 8.5 as supported by PHP agent
  `>= 12.4.0.29`; the PHP agent installation must enforce or verify that
  minimum.
- The PHP image is non-thread-safe, which matches New Relic PHP agent support.
- Magento 2 transaction naming is supported by the PHP agent; manual
  transaction names should be added only where auto-naming is insufficient.
- RabbitMQ monitoring is planned through the New Relic OpenTelemetry RabbitMQ
  receiver path using the management API.
- Nginx monitoring is planned through the New Relic OpenTelemetry NGINX receiver
  path and requires local status endpoints.
- Redis can use the New Relic Redis integration with remote monitoring.
- MySQL, OpenSearch, Varnish, and PHP-FPM internals will use exporters and
  OpenMetrics because current local-service native New Relic integrations were
  not confirmed for this stack.

## Current Limitations

- Local Docker monitoring does not perfectly represent production capacity,
  availability, or network behavior.
- `phpmyadmin` is a local convenience service and will receive only basic
  container-level telemetry.
- Exporter images and metric names can change over time; verification must
  confirm actual metric names before dashboards and alerts are treated as final.
- A New Relic account and license key are required before telemetry can appear
  in New Relic.
- Without outbound internet access, agents and collectors can run locally but
  cannot send telemetry.

## References Checked

- New Relic PHP agent compatibility and requirements.
- New Relic PHP agent Docker/container installation.
- New Relic PHP agent Magento-specific functionality.
- New Relic Docker container monitoring.
- New Relic OpenTelemetry RabbitMQ integration.
- New Relic OpenTelemetry NGINX integration.
- New Relic Redis integration.
- New Relic log forwarding with infrastructure agent.
- New Relic OpenMetrics integration configuration.
