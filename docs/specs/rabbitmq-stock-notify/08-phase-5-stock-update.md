# Phase 5: Stock Update

## Goal

Increase Magento product stock only once per ERP event.

## Files

- `magento/app/code/Training/StockNotifyQueue/Model/Inventory/StockUpdater.php`
- `magento/app/code/Training/StockNotifyQueue/Model/Event/StockEventProcessor.php`

## Behavior

- Check `event_id` first through `training_erp_stock_event`.
- Load product by SKU.
- Read current stock.
- Increase stock by `qty_delta`.
- Save stock.
- Log old and new stock quantity.
- Mark event `processed`.
- If SKU is invalid, mark event `failed` or throw a controlled exception.

## Acceptance Criteria

- First message increases stock.
- Duplicate `event_id` does not increase stock again.
- Invalid SKU marks event failed or throws controlled exception.
- Logs old and new stock quantity.

## Manual Test Commands

From `magento/`:

```bash
php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
tail -n 100 var/log/system.log
```

From repo root:

```bash
docker compose exec php-fpm php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
docker compose exec php-fpm php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
docker compose exec php-fpm php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
docker compose exec php-fpm php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
docker compose exec php-fpm tail -n 100 var/log/system.log
```

## What You Learned

- How to protect a state mutation with an idempotency record.
- Why stock update belongs behind a small service.
- How duplicate queue messages can otherwise cause real business damage.

