# Phase 1: Module Skeleton

## Goal

Create Magento module `Training_StockNotifyQueue`.

## Files

- `magento/app/code/Training/StockNotifyQueue/composer.json`
- `magento/app/code/Training/StockNotifyQueue/registration.php`
- `magento/app/code/Training/StockNotifyQueue/etc/module.xml`

## Behavior

- Add a module-level Composer package definition for `training/module-stock-notify-queue`.
- Declare PSR-4 autoloading for `Training\StockNotifyQueue\`.
- Include `registration.php` in Composer autoload files.
- Register module name `Training_StockNotifyQueue`.
- Declare module setup version only if needed by the Magento version.
- Do not add queue XML, commands, schema, models, or frontend files yet.

## Acceptance Criteria

- Module can be enabled.
- `setup:upgrade` works.
- Module appears in module status.
- Module `composer.json` is valid JSON and contains the expected module name and autoload mapping.

## Manual Test Commands

From `magento/`:

```bash
composer validate app/code/Training/StockNotifyQueue/composer.json
php bin/magento module:enable Training_StockNotifyQueue
php bin/magento setup:upgrade
php bin/magento module:status Training_StockNotifyQueue
```

From repo root:

```bash
docker compose exec php-fpm composer validate app/code/Training/StockNotifyQueue/composer.json
docker compose exec php-fpm php bin/magento module:enable Training_StockNotifyQueue
docker compose exec php-fpm php bin/magento setup:upgrade
docker compose exec php-fpm php bin/magento module:status Training_StockNotifyQueue
```

## What You Learned

- How Magento discovers local modules.
- How a local Magento module can describe itself with Composer metadata.
- How module registration differs from module behavior.
- Why spec-driven phases keep the first step intentionally small.
