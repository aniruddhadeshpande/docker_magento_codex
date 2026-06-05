# RabbitMQ Spec

## Goal

Define RabbitMQ as Magento's message queue service for the local Docker learning stack.

Magento owns queue publishing and consumer behavior. This spec only defines the RabbitMQ infrastructure and Magento-facing configuration.

## Service Requirements

The Docker infrastructure must include:

```text
rabbitmq
```

Use image:

```text
rabbitmq:4.2-management
```

If the exact tag is unavailable, document the selected RabbitMQ 4.2-compatible management image.

Expose the management UI on:

```text
http://localhost:15672
```

Use credentials from `.env`:

```env
RABBITMQ_DEFAULT_USER=guest
RABBITMQ_DEFAULT_PASS=guest
RABBITMQ_MANAGEMENT_PORT=15672
```

Store RabbitMQ data in:

```text
rabbitmq_data
```

## Compose Contract

The final `docker-compose.yml` must define the RabbitMQ service with these settings:

```yaml
rabbitmq:
  image: rabbitmq:4.2-management
  restart: unless-stopped
  environment:
    RABBITMQ_DEFAULT_USER: ${RABBITMQ_DEFAULT_USER}
    RABBITMQ_DEFAULT_PASS: ${RABBITMQ_DEFAULT_PASS}
  ports:
    - "${RABBITMQ_MANAGEMENT_PORT:-15672}:15672"
  volumes:
    - rabbitmq_data:/var/lib/rabbitmq
  networks:
    - learning_network
  healthcheck:
    test: ["CMD", "rabbitmq-diagnostics", "-q", "ping"]
    interval: 10s
    timeout: 5s
    retries: 10
    start_period: 30s
```

Do not expose AMQP port `5672` to the host by default. Magento should connect to RabbitMQ through the internal Docker hostname `rabbitmq` and container port `5672`.

## Magento Configuration

Magento setup must receive RabbitMQ values from `.env` and connect by Docker service name:

```text
rabbitmq
```

Expected Magento setup values:

```text
queue-default-host=rabbitmq
queue-default-port=5672
queue-default-user=${RABBITMQ_DEFAULT_USER}
queue-default-pass=${RABBITMQ_DEFAULT_PASS}
```

The implementation may document how to run Magento consumers later, but this infrastructure spec does not require a dedicated consumer container yet.

## Healthcheck

RabbitMQ must include a healthcheck using:

```bash
rabbitmq-diagnostics ping
```

Use the quiet form `rabbitmq-diagnostics -q ping` in Compose so healthcheck output stays readable.

## Image Tag Caveat

`rabbitmq:4.2-management` is the required learning image because Magento Open Source 2.4.9 requires RabbitMQ 4.2 and the management UI is useful for inspection. The final implementation may pin an exact patch tag such as `rabbitmq:4.2.x-management` if deterministic rebuilds are preferred.

## Acceptance Criteria

- RabbitMQ container starts healthy.
- RabbitMQ management UI is reachable at `http://localhost:15672`.
- RabbitMQ data persists in `rabbitmq_data`.
- Magento setup can connect to RabbitMQ using host `rabbitmq`.
- RabbitMQ connection details are read from `.env`.
