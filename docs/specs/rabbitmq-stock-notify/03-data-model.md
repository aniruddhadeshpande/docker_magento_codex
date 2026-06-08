# Data Model

## Goal

Define database-backed idempotency and durable notification records.

## Table: training_erp_stock_event

Purpose: prevent duplicate ERP stock event processing.

Columns:

- `entity_id`
- `event_id`
- `sku`
- `qty_delta`
- `source_code`
- `status`
- `error_message`
- `created_at`
- `processed_at`
- `updated_at`

Unique key:

```text
UNIQUE(event_id)
```

Allowed statuses:

- `received`
- `processing`
- `processed`
- `failed`
- `duplicate`

## Table: training_customer_notification

Purpose: durable customer notification source of truth.

Columns:

- `entity_id`
- `notification_id`
- `event_id`
- `sku`
- `customer_id`
- `email`
- `title`
- `message`
- `status`
- `created_at`
- `updated_at`

Unique key:

```text
UNIQUE(event_id, sku, customer_id)
```

Allowed statuses:

- `pending`
- `published`
- `sent`
- `failed`
- `read`

## Table: training_notification_delivery

Purpose: track delivery per communication channel.

Columns:

- `entity_id`
- `notification_id`
- `customer_id`
- `channel`
- `status`
- `attempt_count`
- `last_error`
- `sent_at`
- `delivered_at`
- `created_at`
- `updated_at`

Unique key:

```text
UNIQUE(notification_id, channel)
```

Allowed channels:

- `email`
- `websocket`

Allowed statuses:

- `pending`
- `sent`
- `delivered`
- `failed`
- `offline`
- `skipped`

## Idempotency Ownership

- ERP event processing is guarded by `training_erp_stock_event.event_id`.
- Customer notification creation is guarded by `(event_id, sku, customer_id)`.
- Channel delivery is guarded by `(notification_id, channel)`.

