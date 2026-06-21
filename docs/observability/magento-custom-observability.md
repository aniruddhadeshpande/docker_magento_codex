# Magento Custom Observability Decision

## Decision

Do not add a `Training/NewRelicObservability` Magento module yet.

The current implementation already provides the first useful layer:

- PHP APM extension in the `php-fpm` image.
- New Relic PHP daemon in the optional `observability` profile.
- Container, log, RabbitMQ, Redis, Nginx, MySQL, OpenSearch, Varnish, and PHP-FPM metric wiring.
- Existing `Training_StockNotifyQueue` logs for stock publish, consume, duplicate, failure, and success paths.

Adding a Magento module before verifying those signals in New Relic would add code and maintenance without proving a missing signal.

## When To Add A Module

Add a small custom module only after baseline verification shows one of these gaps:

- Queue publish and consume work cannot be separated clearly in APM.
- Cron jobs appear only as generic CLI transactions.
- Stock queue failures are hard to find from logs and errors alone.
- New Relic attributes are needed for safe low-cardinality dimensions.

## Safe Attribute Candidates

Only use values that are operational and low-cardinality:

- `magento.area`
- `magento.cron.job_code`
- `magento.queue.consumer`
- `magento.queue.topic`
- `magento.queue.status`
- `magento.stock.source_code`

Do not send customer IDs, emails, session IDs, order IDs, cart IDs, request bodies, cookies, authorization headers, payment data, raw addresses, or raw SKUs as New Relic attributes.

## Preferred Future Shape

If a module becomes necessary, keep it small:

- One helper service that checks whether the New Relic PHP extension functions exist.
- Plugins around known queue/cron entry points only where automatic instrumentation is insufficient.
- No direct `ObjectManager` usage.
- Dependency injection for logger/config helpers.
- Defensive no-op behavior when the extension is absent.

## Manual Test Commands

```bash
docker compose exec -T php-fpm php bin/magento module:status Training_StockNotifyQueue
docker compose exec -T php-fpm php bin/magento list | grep training:erp:stock-increase
docker compose exec -T php-fpm php bin/magento queue:consumers:list | grep training.erp.stock.increase.consumer
```

No `bin/magento setup:upgrade` is required for this phase because no Magento module was added.
