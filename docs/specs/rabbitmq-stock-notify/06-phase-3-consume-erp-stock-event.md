# Phase 3: Consume ERP Stock Event

## Goal

Create queue topology and a consumer that receives ERP stock increase messages.

## Files

- `magento/app/code/Training/StockNotifyQueue/etc/queue_topology.xml`
- `magento/app/code/Training/StockNotifyQueue/etc/queue_consumer.xml`
- `magento/app/code/Training/StockNotifyQueue/Model/Queue/StockIncreaseConsumer.php`

## Behavior

- Bind `training.erp.stock.increase` to `training.erp.stock.increase.queue`.
- Register consumer `training.erp.stock.increase.consumer`.
- Handler method: `Training\StockNotifyQueue\Model\Queue\StockIncreaseConsumer::process`.
- Decode JSON.
- Validate `event_id`, `sku`, and `qty_delta`.
- Log the payload.
- Throw an exception for invalid payload.
- Do not update stock yet.

## Acceptance Criteria

- Message reaches consumer.
- Payload appears in log.
- Invalid payload throws exception.

## Manual Test Commands

From `magento/`:

```bash
php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
tail -n 100 var/log/system.log
```

From repo root:

```bash
docker compose exec php-fpm php bin/magento training:erp:stock-increase ABC-001 10 ERP-STOCK-1001
docker compose exec php-fpm php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
docker compose exec php-fpm tail -n 100 var/log/system.log
```

## What You Learned

- How Magento queue topology binds a topic to a queue.
- How queue consumers call handler methods.
- Why consuming and business processing can be introduced separately.

