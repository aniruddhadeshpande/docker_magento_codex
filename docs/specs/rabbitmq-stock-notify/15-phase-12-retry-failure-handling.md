# Phase 12: Retry And Failure Handling

## Goal

Make failures visible and retryable.

## Files

- `magento/app/code/Training/StockNotifyQueue/Console/Command/RetryFailedStockEvents.php`
- `magento/app/code/Training/StockNotifyQueue/Console/Command/RetryFailedNotifications.php`
- `magento/app/code/Training/StockNotifyQueue/Model/Event/StockEventProcessor.php`
- `magento/app/code/Training/StockNotifyQueue/Model/Notification/EmailNotificationSender.php`
- `magento/app/code/Training/StockNotifyQueue/Model/Notification/WebSocketNotificationSender.php`

## Behavior

- Failed stock event is visible in `training_erp_stock_event`.
- Failed notification delivery is visible in `training_notification_delivery`.
- Retry command can retry failed stock records.
- Retry command can retry failed delivery records.
- Duplicate events are not treated as failure.
- WebSocket offline is not a hard failure.

Commands:

```bash
php bin/magento training:erp:retry-failed-events
php bin/magento training:notification:retry-failed-deliveries
```

## Acceptance Criteria

- Failed stock events can be found and retried.
- Failed notification deliveries can be found and retried.
- Duplicate events remain safe no-ops.
- Offline websocket delivery remains `offline`, not `failed`, unless a real gateway error occurs.

## Manual Test Commands

From `magento/`:

```bash
php bin/magento training:erp:retry-failed-events
php bin/magento training:notification:retry-failed-deliveries
```

From repo root:

```bash
docker compose exec php-fpm php bin/magento training:erp:retry-failed-events
docker compose exec php-fpm php bin/magento training:notification:retry-failed-deliveries
```

## What You Learned

- How retry-safe processing differs from blindly re-running work.
- Why failure states should be visible in durable tables.
- Why offline WebSocket delivery is expected, not exceptional.

