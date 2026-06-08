# Phase 8: Email Notification Consumer

## Goal

Send or simulate email using a separate queue consumer.

## Files

- `magento/app/code/Training/StockNotifyQueue/Model/Queue/EmailNotificationConsumer.php`
- `magento/app/code/Training/StockNotifyQueue/Model/Notification/DeliveryRepository.php`
- `magento/app/code/Training/StockNotifyQueue/Model/Notification/EmailNotificationSender.php`

## Behavior

- Receive `training.customer.stock.notification.created` message.
- Check delivery row with `channel=email`.
- Create delivery row if needed.
- If delivery is already `sent`, return safely.
- Simulate email by logging first.
- Mark email delivery `sent`.
- Use `UNIQUE(notification_id, channel)`.

## Acceptance Criteria

- Email consumer receives notification event.
- Delivery row created with `channel=email`.
- Duplicate RabbitMQ message does not send duplicate email.
- Status becomes `sent`.

## Manual Test Commands

From `magento/`:

```bash
php bin/magento queue:consumers:start training.customer.stock.notification.email.consumer --max-messages=1
tail -n 100 var/log/system.log
```

From repo root:

```bash
docker compose exec php-fpm php bin/magento queue:consumers:start training.customer.stock.notification.email.consumer --max-messages=1
docker compose exec php-fpm tail -n 100 var/log/system.log
```

## What You Learned

- Why delivery belongs in a separate consumer from stock processing.
- How per-channel idempotency prevents duplicate communication.
- How to simulate an integration before sending real emails.

