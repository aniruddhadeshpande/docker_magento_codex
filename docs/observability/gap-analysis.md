# Observability Gap Analysis

## Purpose

This document compares the current Magento Docker stack against the telemetry
needed for useful DevOps troubleshooting and Magento architecture analysis.

Legend:

- `Yes`: available today.
- `Partial`: available only through manual commands, container healthchecks, or
  framework defaults.
- `No`: not centrally collected or queryable today.

## Component Gaps

| Component | Metrics | Logs | Traces | Alerts | Current Gap | Proposed New Relic Solution |
| --- | --- | --- | --- | --- | --- | --- |
| Magento | Partial | Partial | No | No | Magento writes local logs and exposes CLI state, but there is no APM, error analytics, deployment markers, transaction naming review, or trace correlation. | New Relic PHP APM agent, safe custom attributes, logs in context, dashboards for transactions, errors, DB, cache, search, queue, cron, and CLI. |
| PHP-FPM | Partial | Partial | No | No | Healthcheck validates config only. No active/idle worker count, saturation, slow requests, memory trend, or process restart visibility. | PHP agent for app work plus PHP-FPM exporter/OpenMetrics for pool metrics. |
| Nginx | Partial | Partial | No | No | Access/error logs are local. No request-rate, status-code, latency, upstream timing, active connection, or 5xx alerting. | NGINX OpenTelemetry receiver plus status endpoint; forward access/error logs with metadata. |
| TLS proxy | Partial | Partial | No | No | HTTPS entrypoint logs locally, but no connection/request metrics or edge error alerts. | NGINX OpenTelemetry receiver and log forwarding with `service.name=tls-proxy`. |
| Varnish | Partial | Partial | No | No | VCL exposes cache behavior in response headers, but no central cache hit ratio, backend failures, bans, storage, or restart metrics. | Varnish exporter/OpenMetrics plus Varnish log forwarding where practical. |
| RabbitMQ | Partial | Partial | No | No | Management UI and healthcheck exist, but queue depth, unacked messages, publish/delivery rates, consumers, alarms, and dead-letter trends are not monitored. | OpenTelemetry Collector RabbitMQ receiver via management API, RabbitMQ logs, queue backlog/missing consumer alerts. |
| Redis | Partial | Partial | No | No | Redis healthcheck exists, but memory, fragmentation, hit ratio, evictions, blocked clients, commands/sec, and keyspace growth are not monitored. | New Relic Redis integration with remote monitoring against `redis:6379`, plus Redis log forwarding. |
| MySQL | Partial | Partial | No | No | Healthcheck validates availability only. No central connections, query throughput, slow queries, locks, deadlocks, buffer pool, or disk usage. | MySQL exporter/OpenMetrics, conservative slow-query guidance, infrastructure disk metrics, DB dashboard and alerts. |
| OpenSearch | Partial | Partial | No | No | Healthcheck calls cluster health, but no JVM, heap, shard, rejected operations, latency, indexing, search failure, or disk monitoring. | OpenSearch exporter/OpenMetrics and log forwarding; dashboards and yellow/red cluster alerts. |
| Docker containers | Partial | Partial | No | No | `docker compose ps` and Docker logs are manual. No CPU, memory, network, restart, OOM, uptime, or health status telemetry. | New Relic infrastructure agent Docker monitoring, container log forwarding, restart/unhealthy alerts. |
| Docker host | No | Partial | No | No | Host CPU, memory, disk, network, Docker daemon state, and filesystem pressure are not monitored. | New Relic infrastructure agent on Docker host/container with host mounts and Docker socket access. |
| Magento cron | Partial | Partial | No | No | Magento cron tables and logs can be inspected manually, but there is no last-success, failed-job, missed-job, duration, or stuck-job alerting. | PHP APM for CLI jobs plus safe Magento custom events/queries for cron status if automatic telemetry is insufficient. |
| Magento queue consumers | Partial | Partial | No | No | Consumer can be run manually and RabbitMQ UI can show queues, but no consumer liveness, processing duration, failure, or backlog alerting. | RabbitMQ OTel receiver, PHP background transactions for consumers, optional safe custom attributes for consumer/topic names. |
| External HTTP APIs | Partial | Partial | No | No | Magento/Guzzle dependencies may be visible only in application logs; no central external latency/error view. | PHP APM external service instrumentation and distributed tracing. |
| Frontend/browser activity | No | No | No | No | No real-user monitoring, page load timing, browser errors, Ajax timing, or frontend/backend correlation. | New Relic Browser via PHP agent auto-instrumentation first; manual injection only if needed. |
| phpMyAdmin | Partial | Partial | No | No | Convenience UI has Docker logs but no healthcheck or telemetry; it is not part of Magento runtime path. | Container metrics/logs only. No app-level instrumentation planned unless needed. |

## Highest Priority Gaps

1. Magento APM, errors, and slow transactions.
2. Container and host metrics.
3. Centralized logs with trace correlation.
4. RabbitMQ queue depth and missing-consumer visibility.
5. Redis memory, evictions, and cache hit ratio.
6. MySQL connection, latency, slow-query, and deadlock visibility.
7. OpenSearch health, JVM, disk, shard, and search latency visibility.
8. Varnish cache hit ratio and backend failure visibility.
9. Magento cron and consumer liveness.
10. Dashboards, alerts, and repeatable verification/runbooks.

## Data Privacy Gaps

The current stack does not forward telemetry externally. When New Relic is added,
the implementation must explicitly prevent sensitive data from leaving the local
environment.

Do not collect or send:

- Passwords, API keys, bearer tokens, cookies, session IDs, form keys, or auth
  headers.
- Payment data or raw checkout payloads.
- Full customer names, emails, addresses, phone numbers, cart IDs, order IDs, or
  customer IDs.
- Raw request bodies.
- Full URLs containing unbounded query strings.
- Queue message IDs or arbitrary message payloads as high-cardinality
  attributes.

Safe low-cardinality examples:

- Environment name.
- Deployment version.
- Store, website, and area codes.
- Request type.
- Route/controller/action where already normalized by Magento or the PHP agent.
- Queue topic and consumer names.
- Cron job code.

## Acceptance Review

This table covers every service in `docker-compose.yml` plus Magento runtime
flows that are not first-class Docker services: cron, queue consumers, external
HTTP APIs, and browser activity.
