# Phase 13: Tests

## Goal

Add tests for idempotency and duplicate prevention.

## Test Cases

1. Valid stock event payload.
2. Missing `event_id`.
3. Duplicate `event_id`.
4. Stock updater called once.
5. Duplicate notification row skipped.
6. Email consumer duplicate message does not resend.
7. WebSocket consumer duplicate message does not push twice.
8. Offline WebSocket customer does not fail email delivery.

## Behavior

- Cover business idempotency, not only happy paths.
- Prefer focused service tests where possible.
- Use integration tests where Magento database behavior or queue configuration must be verified.
- Keep tests readable for learning.

## Acceptance Criteria

- Duplicate ERP stock event does not update stock twice.
- Duplicate notification row is prevented by code and database unique key.
- Duplicate email delivery message does not send twice.
- Duplicate WebSocket delivery message does not push twice.
- Offline WebSocket status does not block email status.

## Manual Test Commands

Exact test commands depend on the selected Magento test type.

Possible commands from `magento/`:

```bash
vendor/bin/phpunit
```

Possible commands from repo root:

```bash
docker compose exec php-fpm vendor/bin/phpunit
```

## What You Learned

- How to test at-least-once delivery assumptions.
- How unique keys and repositories work together.
- How to prove duplicate prevention instead of only hoping it works.

