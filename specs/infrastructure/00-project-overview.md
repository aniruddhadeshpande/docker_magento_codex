# Project Overview: Magento Open Source Docker Learning Stack

## Goal

Build a specification-driven Docker learning project for installing and studying Magento Open Source 2.4.9 infrastructure locally.

This project is not a production Magento deployment. It is an educational local stack that mirrors common Magento infrastructure concepts with readable Docker services, clear service boundaries, and repeatable verification steps.

Magento itself owns application behavior such as catalog schema creation, admin setup, message publishing, indexing, cache invalidation, and product CRUD. Infrastructure specs must not create dummy CRUD tables or procedural PHP demo code unless a later learning spec explicitly asks for a throwaway sandbox app.

## Target Platform

Target Magento version:

```text
Magento Open Source 2.4.9
```

System requirements should follow Adobe Commerce / Magento Open Source on-premises requirements for the 2.4.9 release family:

```text
PHP 8.5
MySQL 8.4
Nginx 1.30
Varnish 7
OpenSearch 3
RabbitMQ 4.2
Redis 6.2
Composer 2.9.3 or newer
```

If an exact container image is temporarily unavailable during implementation, Codex must document the blocker and avoid silently downgrading without user approval.

## Architecture

Primary request flow:

```text
User -> TLS proxy :443 -> Varnish -> Nginx -> PHP-FPM -> MySQL
```

Additional service integrations:

```text
PHP -> RabbitMQ
PHP -> OpenSearch
PHP -> Redis
```

The application should be available locally at:

```text
https://magento.docker
```

The host machine must map the domain to localhost:

```text
127.0.0.1 magento.docker
```

## Learning Objectives

- Understand Magento-oriented LEMP-style containerization with PHP-FPM, Nginx, and MySQL.
- Understand reverse proxy and cache layering with TLS termination and Varnish.
- Understand service-to-service communication through Docker networks.
- Understand persistent service data through Docker volumes.
- Understand Magento search service integration using OpenSearch.
- Understand Magento message queue integration using RabbitMQ.
- Understand Magento cache and session storage integration using Redis.
- Practice specification-driven, agentic coding with Codex.

## Expected Project Structure

Implementation should eventually create this structure:

```text
/project-root
├── docker-compose.yml
├── .env
├── certs/
│   ├── magento.docker.crt
│   └── magento.docker.key
├── tls/
│   └── default.conf
├── nginx/
│   └── default.conf
├── varnish/
│   └── default.vcl
├── php/
│   └── Dockerfile
├── magento/
│   └── <Magento Open Source application files>
├── auth/
│   └── auth.json.example
└── specs/
    └── infrastructure/
        ├── 00-project-overview.md
        ├── 01-infra.md
        ├── 02-php-app.md
        ├── 03-database.md
        ├── 04-rabbitmq.md
        ├── 05-opensearch.md
        ├── 06-varnish-cache.md
        ├── 07-verification.md
        ├── 08-nginx.md
        └── 09-redis.md
```

The `magento/` directory is the application root mounted into PHP-FPM and Nginx. During implementation, Magento may be installed through Composer into this directory.

## Specification-Driven Implementation Rule

Implementation must happen spec-by-spec.

Codex should:

1. Read the relevant spec before making changes.
2. Implement only the scope described by that spec.
3. Avoid unrelated files and unrelated refactors.
4. Run the acceptance commands defined in the spec.
5. Report what changed, what passed, and what remains.

Recommended implementation order:

1. `01-infra.md`
2. `03-database.md`
3. `02-php-app.md`
4. `04-rabbitmq.md`
5. `05-opensearch.md`
6. `06-varnish-cache.md`
7. `08-nginx.md`
8. `09-redis.md`
9. `07-verification.md`

## Constraints

- Keep code and configuration simple, readable, and well commented.
- Use Docker networks for service communication.
- Use Docker volumes for persistent service data.
- Use environment variables for credentials and service settings.
- Add healthchecks for services where practical.
- Add restart policies for long-running services.
- Do not create a custom PHP application in place of Magento.
- Do not create dummy MySQL schema for products, customers, orders, or catalog data.
- Let Magento setup commands create and manage Magento database tables.
- Prefer educational clarity over production hardening.
