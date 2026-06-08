# Phase 11: Frontend Notification UI

## Goal

Show real-time stock notifications on the Magento frontend.

## Files

- Frontend JavaScript component.
- Layout XML if needed.
- Template if needed.

Use Magento frontend paths under:

```text
magento/app/code/Training/StockNotifyQueue/view/frontend
```

## Behavior

- Connect to `ws://localhost:8080`.
- Identify logged-in customer.
- Receive `stock_notification` messages.
- Dedupe by `notification_id`.
- Show a toast or alert only once per `notification_id`.
- Send ack payload:

```json
{
  "type": "notification_ack",
  "notification_id": "1001"
}
```

- Keep the design open for a future unread notification center.

## Acceptance Criteria

- Logged-in customer receives live notification.
- Duplicate `notification_id` is ignored.
- Frontend can later be extended to unread notification center.

## Manual Test Commands

From repo root:

```bash
docker compose exec php-fpm php bin/magento cache:clean
docker compose exec php-fpm php bin/magento setup:static-content:deploy -f
```

Open the Magento frontend as a logged-in customer and trigger the final demo flow.

## What You Learned

- How frontend real-time UI connects to an external WebSocket service.
- Why browser-side dedupe is still needed even with backend idempotency.
- How durable backend notifications and temporary live notifications complement each other.

