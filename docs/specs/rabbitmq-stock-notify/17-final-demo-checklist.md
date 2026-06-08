# Final Demo Checklist

## Goal

Run the full stock notification workflow end to end.

## Prerequisites

- RabbitMQ is running.
- Magento is running.
- WebSocket gateway is running.
- A frontend browser is open as a logged-in customer.
- Customer is subscribed to stock alert for SKU `ABC-001`.

## Start Services

From repo root:

```bash
docker compose up -d
```

From `tools/websocket-gateway/`:

```bash
npm start
```

## Publish ERP Stock Event

From `magento/`:

```bash
php bin/magento training:erp:stock-increase ABC-001 10 ERP-DEMO-1
```

From repo root:

```bash
docker compose exec php-fpm php bin/magento training:erp:stock-increase ABC-001 10 ERP-DEMO-1
```

## Run Consumers

From `magento/`:

```bash
php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
php bin/magento queue:consumers:start training.customer.stock.notification.email.consumer --max-messages=1
php bin/magento queue:consumers:start training.customer.stock.notification.websocket.consumer --max-messages=1
```

From repo root:

```bash
docker compose exec php-fpm php bin/magento queue:consumers:start training.erp.stock.increase.consumer --max-messages=1
docker compose exec php-fpm php bin/magento queue:consumers:start training.customer.stock.notification.email.consumer --max-messages=1
docker compose exec php-fpm php bin/magento queue:consumers:start training.customer.stock.notification.websocket.consumer --max-messages=1
```

## Expected Result

- Stock increases once.
- Customer notification row is created once.
- Email delivery row is created once.
- WebSocket delivery row is created once.
- Frontend notification is shown once.
- Duplicate `ERP-DEMO-1` event does not duplicate stock.
- Duplicate `ERP-DEMO-1` event does not duplicate email.
- Duplicate `ERP-DEMO-1` event does not duplicate WebSocket toast.

## Duplicate Demo

Publish the same event again:

```bash
php bin/magento training:erp:stock-increase ABC-001 10 ERP-DEMO-1
```

Then run the consumers again and confirm all duplicate protections still hold.

## What You Learned

- How Magento queue consumers can be chained through durable business events.
- How RabbitMQ fan-out supports separate communication modes.
- Why idempotency belongs in the database when queues are at-least-once.

