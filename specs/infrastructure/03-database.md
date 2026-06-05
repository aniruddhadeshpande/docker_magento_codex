# Database Spec

## Goal

Define the MySQL service required by Magento Open Source 2.4.9.

Magento owns schema creation and data management. This spec must not create dummy product tables, seed catalog data, or custom SQL for Magento entities.

## Service Requirement

Use:

```text
mysql:8.4
```

The service name must be:

```text
mysql
```

Magento and other containers must connect to MySQL through the Docker service name `mysql`.

## Compose Contract

The final `docker-compose.yml` must define the MySQL service with these settings:

```yaml
mysql:
  image: mysql:8.4
  restart: unless-stopped
  environment:
    MYSQL_DATABASE: ${MYSQL_DATABASE}
    MYSQL_USER: ${MYSQL_USER}
    MYSQL_PASSWORD: ${MYSQL_PASSWORD}
    MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
  volumes:
    - mysql_data:/var/lib/mysql
  networks:
    - learning_network
  healthcheck:
    test: ["CMD-SHELL", "mysqladmin ping -h localhost -uroot -p$${MYSQL_ROOT_PASSWORD} --silent"]
    interval: 10s
    timeout: 5s
    retries: 10
    start_period: 30s
```

Do not expose MySQL to the host by default. Magento, PHP-FPM, and setup commands should use the internal Docker hostname `mysql` and container port `3306`.

## Persistence

Store data in:

```text
mysql_data
```

The volume must persist Magento database data across normal restarts.

## Environment Variables

The MySQL service must read credentials from `.env`:

```env
MYSQL_DATABASE=magento
MYSQL_USER=magento
MYSQL_PASSWORD=magento_password
MYSQL_ROOT_PASSWORD=root_password
```

Magento setup must use the same values when running installation commands.

## Initialization Rules

- Do not create `mysql/init.sql` for Magento schema.
- Do not create custom `products`, `customers`, `orders`, or catalog tables.
- Let `bin/magento setup:install` create the Magento schema.
- If a database already exists, implementation should document how to reset the environment with `docker compose down -v`.
- Do not mount `/docker-entrypoint-initdb.d` unless a later spec explicitly requires a Magento-compatible initialization script.

## Healthcheck

The MySQL service must include a healthcheck using:

```bash
mysqladmin ping
```

The healthcheck should authenticate with `MYSQL_ROOT_PASSWORD` so that a TCP-ready but misconfigured database is caught during verification.

## Image Tag Caveat

`mysql:8.4` tracks the current MySQL 8.4 LTS patch release. This is acceptable for a learning stack aligned to Magento Open Source 2.4.9, but the final implementation may pin an exact patch tag such as `mysql:8.4.x` if reproducible builds are preferred.

## Acceptance Criteria

- MySQL container starts healthy.
- Magento database credentials are read from `.env`.
- Magento setup can connect to MySQL using host `mysql`.
- Magento setup creates the required Magento tables.
- MySQL data persists across `docker compose down` and `docker compose up`.
- MySQL data is removed by `docker compose down -v`.
