# Phase 4: Idempotent Event Store

## Goal

Prevent duplicate stock event processing by storing ERP events in Magento.

## Files

- `magento/app/code/Training/StockNotifyQueue/etc/db_schema.xml`
- `magento/app/code/Training/StockNotifyQueue/Model/Event/StockEventRepository.php`
- `magento/app/code/Training/StockNotifyQueue/Model/Event/StockEventProcessor.php`
- `magento/app/code/Training/StockNotifyQueue/Model/Queue/StockIncreaseConsumer.php`

## Behavior

- Create table `training_erp_stock_event`.
- Insert event row with `event_id`.
- Use `UNIQUE(event_id)`.
- If `event_id` already exists, return safely.
- Do not throw for duplicate event.
- Mark new event as `processed`.
- Do not update stock yet.

Allowed statuses:

- `received`
- `processing`
- `processed`
- `failed`
- `duplicate`

## Acceptance Criteria

- Same `event_id` creates only one row.
- Duplicate event does not fail consumer.
- Duplicate event does not process business logic again.

## Manual Test Commands

From `magento/`:

```bash
php bin/magento setup:upgrade
php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=2
```

From repo root:

```bash
docker compose exec php-fpm php bin/magento setup:upgrade
docker compose exec php-fpm php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
docker compose exec php-fpm php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
docker compose exec php-fpm php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=2
```

## What You Learned

- Why at-least-once queues require database idempotency.
- How a unique key becomes a business lock.
- Why duplicates should usually be safe no-ops, not hard failures.

