# Phase 10: WebSocket Notification Consumer

## Goal

Consume `notification.created` events and push them to the WebSocket gateway.

## Files

- `magento/app/code/Training/StockNotifyQueue/Model/Queue/WebSocketNotificationConsumer.php`
- `magento/app/code/Training/StockNotifyQueue/Model/Notification/WebSocketNotificationSender.php`
- `magento/app/code/Training/StockNotifyQueue/Model/Notification/DeliveryRepository.php`

## Behavior

- Receive `training.customer.stock.notification.created` message.
- Check delivery row with `channel=websocket`.
- Create delivery row if needed.
- Call WebSocket gateway.
- If customer is online, mark delivery `delivered` or `sent`.
- If customer is offline, mark delivery `offline`.
- Duplicate RabbitMQ message must not create duplicate frontend notification.
- Offline WebSocket delivery must not fail stock event or email delivery.

## Acceptance Criteria

- WebSocket consumer receives event.
- Online customer receives frontend notification.
- Offline customer does not fail business flow.
- Delivery row tracks websocket status.

## Manual Test Commands

From `magento/`:

```bash
php bin/magento queue:consumers:start training.customer.stock.notification.websocket.consumer --max-messages=1
tail -n 100 var/log/system.log
```

From repo root:

```bash
docker compose exec php-fpm php bin/magento queue:consumers:start training.customer.stock.notification.websocket.consumer --max-messages=1
docker compose exec php-fpm tail -n 100 var/log/system.log
```

## What You Learned

- How WebSocket delivery can be modeled as its own channel.
- Why offline is a business state, not a hard failure.
- How channel-level idempotency protects the browser from duplicate pushes.

