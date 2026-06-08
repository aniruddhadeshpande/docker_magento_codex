# Product Spec

## Project

RabbitMQ Stock Notification with Email and WebSocket

Magento module:

```text
Training_StockNotifyQueue
```

Future module path:

```text
magento/app/code/Training/StockNotifyQueue
```

## Goal

Build a learning project that shows how Magento uses RabbitMQ for asynchronous, retry-safe business workflows.

A dummy ERP reports that product stock increased. Magento publishes the ERP event, consumes it, updates stock once, creates durable customer notification records, and fans out notification delivery to separate email and WebSocket consumers.

## Learning Goals

- Publish RabbitMQ messages from Magento.
- Consume Magento queue messages.
- Model subscribe-style fan-out with one topic and multiple queues.
- Define queue topology, publishers, and consumers.
- Use separate consumers for separate responsibilities.
- Treat RabbitMQ as at-least-once delivery.
- Prevent duplicate stock updates, emails, and frontend notifications with database idempotency.
- Keep notifications durable even when a customer is offline.

## Core ERP Event Payload

```json
{
  "event_id": "ERP-STOCK-1001",
  "sku": "ABC-001",
  "qty_delta": 10,
  "source_code": "default",
  "occurred_at": "2026-06-06T10:30:00+05:30"
}
```

## RabbitMQ Topics

```text
training.erp.stock.increase
training.customer.stock.notification.created
```

## RabbitMQ Queues And Consumers

```text
training.erp.stock.increase.queue
training.erp.stock.increase.consumer
Training\StockNotifyQueue\Model\Queue\StockIncreaseConsumer::process
```

```text
training.customer.stock.notification.email.queue
training.customer.stock.notification.email.consumer
Training\StockNotifyQueue\Model\Queue\EmailNotificationConsumer::process
```

```text
training.customer.stock.notification.websocket.queue
training.customer.stock.notification.websocket.consumer
Training\StockNotifyQueue\Model\Queue\WebSocketNotificationConsumer::process
```

## Required Magento Queue Config Files

- `etc/communication.xml`
- `etc/queue_topology.xml`
- `etc/queue_publisher.xml`
- `etc/queue_consumer.xml`

## Idempotency Principle

RabbitMQ must be treated as at-least-once delivery. The module must never assume exactly-once delivery.

Business idempotency belongs in Magento database tables using unique keys:

- `UNIQUE(event_id)` for ERP stock events.
- `UNIQUE(event_id, sku, customer_id)` for customer notifications.
- `UNIQUE(notification_id, channel)` for per-channel delivery.

Duplicate RabbitMQ messages should return safely when business work is already complete.

