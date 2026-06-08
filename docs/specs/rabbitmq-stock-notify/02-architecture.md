# Architecture

## Goal

Define the message flow, fan-out topology, idempotency boundaries, and WebSocket gateway boundary.

## End-To-End Flow

```text
Dummy ERP CLI command
  -> publish training.erp.stock.increase
  -> training.erp.stock.increase.queue
  -> StockIncreaseConsumer
  -> training_erp_stock_event idempotency check
  -> stock update
  -> find stock alert subscribers
  -> create training_customer_notification rows
  -> publish training.customer.stock.notification.created
  -> fan-out to email and websocket queues
  -> EmailNotificationConsumer
  -> WebSocketNotificationConsumer
  -> frontend browser notification
```

## Sequence Diagram

```mermaid
sequenceDiagram
    autonumber
    actor ERP as Dummy ERP CLI
    participant StockTopic as RabbitMQ topic<br/>training.erp.stock.increase
    participant StockQueue as Queue<br/>training.erp.stock.increase.queue
    participant StockConsumer as StockIncreaseConsumer
    participant DB as Magento DB
    participant Inventory as Magento Inventory
    participant NotifyTopic as RabbitMQ topic<br/>training.customer.stock.notification.created
    participant EmailQueue as Email Queue
    participant WsQueue as WebSocket Queue
    participant EmailConsumer as EmailNotificationConsumer
    participant WsConsumer as WebSocketNotificationConsumer
    participant Gateway as Node.js WebSocket Gateway
    actor Browser as Frontend Browser

    ERP->>StockTopic: Publish stock increase JSON
    StockTopic->>StockQueue: Route message
    StockQueue->>StockConsumer: Consume message
    StockConsumer->>DB: Insert training_erp_stock_event by event_id

    alt event_id already exists
        DB-->>StockConsumer: Duplicate key / existing row
        StockConsumer-->>StockQueue: Ack safely without business work
    else new event_id
        DB-->>StockConsumer: Event row created
        StockConsumer->>Inventory: Increase stock by qty_delta
        Inventory-->>StockConsumer: Old and new quantity
        StockConsumer->>DB: Find stock alert subscribers

        loop each subscribed customer
            StockConsumer->>DB: Insert training_customer_notification
            alt notification already exists
                DB-->>StockConsumer: Duplicate notification skipped
            else notification created
                DB-->>StockConsumer: Durable notification row
                StockConsumer->>NotifyTopic: Publish notification.created
            end
        end

        NotifyTopic->>EmailQueue: Fan-out notification.created
        NotifyTopic->>WsQueue: Fan-out notification.created

        par email delivery
            EmailQueue->>EmailConsumer: Consume message
            EmailConsumer->>DB: Upsert delivery channel=email
            alt email already sent
                DB-->>EmailConsumer: Return safely
            else pending email
                EmailConsumer->>EmailConsumer: Simulate/send email
                EmailConsumer->>DB: Mark email delivery sent
            end
        and websocket delivery
            WsQueue->>WsConsumer: Consume message
            WsConsumer->>DB: Upsert delivery channel=websocket
            alt websocket already delivered or sent
                DB-->>WsConsumer: Return safely
            else pending websocket
                WsConsumer->>Gateway: Push stock_notification
                alt customer online
                    Gateway->>Browser: Send stock_notification
                    Browser->>Browser: Dedupe by notification_id
                    Browser->>Gateway: Send notification_ack
                    Gateway-->>WsConsumer: Delivered
                    WsConsumer->>DB: Mark websocket delivered
                else customer offline
                    Gateway-->>WsConsumer: Offline
                    WsConsumer->>DB: Mark websocket offline
                end
            end
        end

        StockConsumer->>DB: Mark ERP event processed
    end
```

## ERP Stock Topic

Topic:

```text
training.erp.stock.increase
```

Queue:

```text
training.erp.stock.increase.queue
```

Consumer:

```text
training.erp.stock.increase.consumer
```

Handler:

```text
Training\StockNotifyQueue\Model\Queue\StockIncreaseConsumer::process
```

## Notification Created Topic

Topic:

```text
training.customer.stock.notification.created
```

Fan-out queues:

```text
training.customer.stock.notification.email.queue
training.customer.stock.notification.websocket.queue
```

Consumers:

```text
training.customer.stock.notification.email.consumer
training.customer.stock.notification.websocket.consumer
```

## Notification Created Payload

```json
{
  "notification_id": "1001",
  "event_id": "ERP-STOCK-1001",
  "sku": "ABC-001",
  "customer_id": 25,
  "email": "customer@example.com",
  "title": "Product back in stock",
  "message": "ABC-001 is available now."
}
```

## WebSocket Boundary

Magento PHP should not hold long-running browser WebSocket connections.

Use a separate Node.js WebSocket gateway. Magento pushes to the gateway from `WebSocketNotificationConsumer`; the gateway owns browser connections and customer socket mapping.

## ER Diagram

```mermaid
erDiagram
    TRAINING_ERP_STOCK_EVENT ||--o{ TRAINING_CUSTOMER_NOTIFICATION : creates
    TRAINING_CUSTOMER_NOTIFICATION ||--o{ TRAINING_NOTIFICATION_DELIVERY : tracks

    TRAINING_ERP_STOCK_EVENT {
        int entity_id PK
        string event_id UK
        string sku
        decimal qty_delta
        string source_code
        string status
        text error_message
        datetime created_at
        datetime processed_at
        datetime updated_at
    }

    TRAINING_CUSTOMER_NOTIFICATION {
        int entity_id PK
        string notification_id UK
        string event_id UK
        string sku UK
        int customer_id UK
        string email
        string title
        text message
        string status
        datetime created_at
        datetime updated_at
    }

    TRAINING_NOTIFICATION_DELIVERY {
        int entity_id PK
        string notification_id UK
        int customer_id
        string channel UK
        string status
        int attempt_count
        text last_error
        datetime sent_at
        datetime delivered_at
        datetime created_at
        datetime updated_at
    }
```

Logical relationships:

- One `training_erp_stock_event` can create many `training_customer_notification` rows.
- One `training_customer_notification` can create one delivery row per channel.
- `training_customer_notification` is unique by `(event_id, sku, customer_id)`.
- `training_notification_delivery` is unique by `(notification_id, channel)`.

## Offline Rule

WebSocket delivery is not durable.

If the customer is offline:

- Mark websocket delivery as `offline`.
- Do not fail the stock event.
- Do not fail email delivery.
- Keep `training_customer_notification` as the durable source of truth.
- Let the frontend fetch unread notifications later in a future extension.
