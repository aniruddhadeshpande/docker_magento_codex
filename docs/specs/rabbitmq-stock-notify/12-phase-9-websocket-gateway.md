# Phase 9: WebSocket Gateway

## Goal

Create a separate Node.js WebSocket gateway for learning.

## Files

- `tools/websocket-gateway/package.json`
- `tools/websocket-gateway/server.js`
- `tools/websocket-gateway/README.md`

## Behavior

- Start WebSocket server at `ws://localhost:8080`.
- Accept browser connections with `customer_id`.
- Store `customer_id` to socket mapping.
- Expose a simple HTTP endpoint for Magento to push notification payloads.
- Push notification to connected browser.
- Log offline customer when no socket exists.
- Receive browser ack payload and log it.

WebSocket frontend payload:

```json
{
  "type": "stock_notification",
  "notification_id": "1001",
  "sku": "ABC-001",
  "title": "Product back in stock",
  "message": "ABC-001 is available now."
}
```

Browser ack payload:

```json
{
  "type": "notification_ack",
  "notification_id": "1001"
}
```

## Acceptance Criteria

- Gateway starts.
- Browser can connect.
- Test notification can be pushed.
- Gateway logs connected `customer_id`.

## Manual Test Commands

From `tools/websocket-gateway/`:

```bash
npm install
npm start
```

Example HTTP push can be documented in the gateway README after the endpoint shape is implemented.

## What You Learned

- Why long-running browser sockets should live outside Magento PHP.
- How a small gateway separates connection state from business state.
- Why WebSocket delivery is not durable by itself.

