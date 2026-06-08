# Learning Roadmap

## Goal

Build the module in small phases so each RabbitMQ and idempotency concept is learned before the next one is introduced.

## Phase Order

1. Module skeleton
2. Publish ERP stock event
3. Consume ERP stock event
4. Idempotent event store
5. Stock update
6. Create customer notification outbox
7. Publish notification.created event
8. Email notification consumer
9. WebSocket gateway
10. WebSocket notification consumer
11. Frontend notification UI
12. Retry and failure handling
13. Tests

## Learning Shape

Each phase should be completed before starting the next phase. Do not add future-phase classes, XML, database tables, commands, or frontend files early.

Each phase should include:

- A short explanation before coding.
- Only the files required by that phase.
- Manual verification commands.
- Acceptance criteria verification.
- A short explanation of what was learned.

## Command Style

Magento-native command examples:

```bash
php bin/magento module:status Training_StockNotifyQueue
```

Repo-local Docker command examples:

```bash
docker compose exec php-fpm php bin/magento module:status Training_StockNotifyQueue
```

Run Docker commands from the repository root. Run Magento-native commands from the `magento/` directory.

