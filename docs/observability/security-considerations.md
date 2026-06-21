# Observability Security Considerations

## Secrets

- Keep real New Relic keys in `.env` or a local secret store.
- Do not commit `.env`, `auth/auth.json`, Magento `env.php` secrets, license
  keys, API keys, or generated dashboard API payloads with real keys.
- Do not print `docker compose config` output into tickets or docs without
  redacting secrets.

## Docker Socket

`newrelic-infra` mounts `/var/run/docker.sock:ro` and host paths so it can read
container metadata and logs. This is acceptable for this local learning stack
only when the `observability` profile is explicitly enabled.

For production, use the least privileged monitoring pattern available on the
target platform.

## Logs

Logs can accidentally contain sensitive data. Keep these defaults:

- Do not enable Magento debug logging unless needed.
- Do not log request bodies.
- Do not log cookies, authorization headers, bearer tokens, form keys, payment
  data, addresses, emails, customer IDs, cart IDs, or order IDs.
- Avoid forwarding the same log through more than one path.

## Attributes And Labels

Safe low-cardinality labels:

- `environment`
- `service.name`
- `magento.area`
- `magento.queue.consumer`
- `magento.queue.topic`
- `magento.cron.job_code`
- `magento.stock.source_code`

Avoid high-cardinality or sensitive labels:

- Raw SKU values.
- Customer, order, cart, session, or message IDs.
- Full URLs with query strings.
- Arbitrary exception messages as attributes.

## Browser Monitoring

Browser auto-instrumentation is supported but should be reviewed before it is
enabled in a shared or production-like environment.

Keep session replay, browser logs, and user tracking off unless there is a
specific learning goal and privacy review.

## Network Exposure

The observability exporters are internal Compose services. Do not publish their
ports to the host unless you need temporary local debugging.

Host-exposed local services remain:

- HTTPS: `${HTTPS_PORT:-443}`
- OpenSearch: `${OPENSEARCH_PORT:-9200}`
- RabbitMQ management: `${RABBITMQ_MANAGEMENT_PORT:-15672}`
- phpMyAdmin: `${PHPMYADMIN_PORT:-8081}`
