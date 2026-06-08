# Phase 6: Create Customer Notification Outbox

## Goal

Create durable customer notification records after stock is updated.

## Files

- `magento/app/code/Training/StockNotifyQueue/etc/db_schema.xml`
- `magento/app/code/Training/StockNotifyQueue/Model/Notification/NotificationCollector.php`
- `magento/app/code/Training/StockNotifyQueue/Model/Notification/NotificationRepository.php`
- `magento/app/code/Training/StockNotifyQueue/Model/Event/StockEventProcessor.php`

## Behavior

- Create table `training_customer_notification`.
- Find customers subscribed to stock notification for the SKU.
- Create one notification row per customer.
- Use `UNIQUE(event_id, sku, customer_id)`.
- Duplicate ERP event must not create duplicate notification rows.
- Do not publish `notification.created` yet.

Allowed statuses:

- `pending`
- `published`
- `sent`
- `failed`
- `read`

## Acceptance Criteria

- Subscribed customers are found.
- Notification rows are created.
- Duplicate event creates no duplicate rows.

## Manual Test Commands

From `magento/`:

```bash
php bin/magento setup:upgrade
php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
```

From repo root:

```bash
docker compose exec php-fpm php bin/magento setup:upgrade
docker compose exec php-fpm php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
docker compose exec php-fpm php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
docker compose exec php-fpm php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
docker compose exec php-fpm php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
```

## What You Learned

- How durable notification records act like an outbox.
- How notification creation is separate from notification delivery.
- How a unique key prevents duplicate customer notification records.

