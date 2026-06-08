# Phase 2: Publish ERP Stock Event

## Goal

Create a CLI command that simulates a dummy ERP publishing a stock increase event.

## Files

- `magento/app/code/Training/StockNotifyQueue/etc/communication.xml`
- `magento/app/code/Training/StockNotifyQueue/etc/queue_publisher.xml`
- `magento/app/code/Training/StockNotifyQueue/etc/di.xml`
- `magento/app/code/Training/StockNotifyQueue/Console/Command/PublishStockIncrease.php`

## Behavior

- Add command `training:erp:stock-increase`.
- Validate `sku`, `qty_delta`, and `event_id`.
- Reject `qty_delta` less than or equal to `0`.
- Publish a JSON message to topic `training.erp.stock.increase`.
- Do not create topology or consumers yet.

Command:

```bash
php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
```

Payload:

```json
{
  "event_id": "ERP-STOCK-1001",
  "sku": "ABC-001",
  "qty_delta": 10,
  "source_code": "default",
  "occurred_at": "2026-06-06T10:30:00+05:30"
}
```

## Acceptance Criteria

- Command exists.
- Command validates `sku`, `qty_delta`, and `event_id`.
- Command publishes JSON message to `training.erp.stock.increase`.
- Command rejects `qty_delta` less than or equal to `0`.

## Manual Test Commands

From `magento/`:

```bash
php bin/magento list | grep training:erp
php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
php bin/magento training:erp:stock-increase ABC-001 0 ERP-STOCK-1002
```

From repo root:

```bash
docker compose exec php-fpm php bin/magento list
docker compose exec php-fpm php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
docker compose exec php-fpm php bin/magento training:erp:stock-increase ABC-001 0 ERP-STOCK-1002
```

## What You Learned

- How Magento CLI commands are registered.
- How `communication.xml` declares a topic.
- How `queue_publisher.xml` maps publication to the message queue system.

