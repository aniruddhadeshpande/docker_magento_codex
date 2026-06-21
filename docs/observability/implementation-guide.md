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

## Generate New Relic API Keys

Use the New Relic API keys UI:

- US: `https://one.newrelic.com/api-keys`
- EU: `https://one.eu.newrelic.com/api-keys`

### Ingest License Key

Create this key first. It is required by the PHP agent, infrastructure agent,
and OTel collector.

1. Log in to New Relic with a user that can manage keys for the target account.
2. Open the API keys UI.
3. Click `Create a key`.
4. Enter a name such as `docker-magento-learning-local-ingest`.
5. Select `Ingest - License` as the key type.
6. Select the account that should receive the Magento telemetry.
7. Save the key and copy the full value immediately.
8. Add it to `.env`:

```dotenv
NEW_RELIC_LICENSE_KEY=<ingest-license-key>
```

### User API Key

Create this key only if you plan to automate dashboards, alerts, or NerdGraph
queries. Agents and metric ingestion do not need it.

1. Open the API keys UI.
2. Click `Create a key`.
3. Enter a name such as `docker-magento-learning-local-user`.
4. Select `User` as the key type.
5. Save the key and copy the full value immediately.
6. Add it to `.env`:

```dotenv
NEW_RELIC_API_KEY=<user-api-key>
NEW_RELIC_ACCOUNT_ID=<target-account-id>
```

### Key Handling Notes

- New Relic temporarily shows the full key value when a license key or user key
  is created. After creation, the API keys UI shows only a truncated value.
- Store generated keys only in `.env` or a local secret store.
- Do not commit generated keys, paste them into tickets, or include them in
  copied command output.
- Create a separate ingest license key for this local stack instead of reusing
  an organization-wide original key. That makes later rotation simple.
- A browser key is not required for this implementation unless manual browser
  instrumentation is added later.

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
