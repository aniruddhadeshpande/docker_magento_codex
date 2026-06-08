# Phase 7: Publish Notification Created Event

## Goal

Publish a `notification.created` event after a durable customer notification row is created.

## Files

- `magento/app/code/Training/StockNotifyQueue/etc/communication.xml`
- `magento/app/code/Training/StockNotifyQueue/etc/queue_topology.xml`
- `magento/app/code/Training/StockNotifyQueue/etc/queue_publisher.xml`
- `magento/app/code/Training/StockNotifyQueue/Model/Notification/NotificationPublisher.php`
- `magento/app/code/Training/StockNotifyQueue/Model/Event/StockEventProcessor.php`

## Behavior

- Add topic `training.customer.stock.notification.created`.
- Fan out to:
  - `training.customer.stock.notification.email.queue`
  - `training.customer.stock.notification.websocket.queue`
- Publish one event per durable `training_customer_notification` row.
- Stock consumer must not send email directly.
- Stock consumer must not call WebSocket gateway directly.

Payload:

```json
{
  "notification_id": "1001",
  "event_id": "ERP-STOCK-1001",
  "sku": "ABC-001",
  "customer_id": 25,
  "email": "customer@example.com",
  "title": "Product back in stock",
  "message": "ABC-001 is available now."
}
```

## Acceptance Criteria

- `notification.created` topic exists.
- Email queue receives event.
- WebSocket queue receives event.
- Stock consumer does not send email directly.
- Stock consumer does not call WebSocket gateway directly.

## Manual Test Commands

From `magento/`:

```bash
php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
```

From repo root:

```bash
docker compose exec php-fpm php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
docker compose exec php-fpm php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
```

Use RabbitMQ management UI at `http://localhost:15672` to inspect queues.

## What You Learned

- How one durable business event can trigger multiple delivery workflows.
- How queue fan-out keeps email and WebSocket delivery independent.
- Why publishers should publish facts, not call every downstream action directly.

