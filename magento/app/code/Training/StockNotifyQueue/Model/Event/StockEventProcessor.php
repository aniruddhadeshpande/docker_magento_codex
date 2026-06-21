<?php

declare(strict_types=1);

namespace Training\StockNotifyQueue\Model\Event;

use Psr\Log\LoggerInterface;
use Throwable;
use Training\StockNotifyQueue\Model\Inventory\StockUpdater;

class StockEventProcessor
{
    public function __construct(
        private readonly StockEventRepository $stockEventRepository,
        private readonly StockUpdater $stockUpdater,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function process(array $payload): void
    {
        $eventId = (string)$payload['event_id'];

        if (!$this->stockEventRepository->insertReceived($payload)) {
            $this->logger->info('Duplicate ERP stock increase event skipped.', [
                'event_id' => $eventId,
                'sku' => $payload['sku'] ?? null,
            ]);
            return;
        }

        $this->stockEventRepository->markProcessing($eventId);

        try {
            $stockChange = $this->stockUpdater->increase(
                (string)$payload['sku'],
                (int)$payload['qty_delta'],
                (string)($payload['source_code'] ?? 'default')
            );
        } catch (Throwable $exception) {
            $this->stockEventRepository->markFailed($eventId, $exception->getMessage());
            $this->logger->error('Training stock increase event failed.', [
                'event_id' => $eventId,
                'sku' => $payload['sku'] ?? null,
                'qty_delta' => $payload['qty_delta'] ?? null,
                'source_code' => $payload['source_code'] ?? null,
                'error' => $exception->getMessage(),
            ]);
            return;
        }

        $this->logger->info('Training stock increase event processed.', [
            'event_id' => $eventId,
            'sku' => $payload['sku'],
            'qty_delta' => $payload['qty_delta'],
            'source_code' => $payload['source_code'] ?? null,
            'occurred_at' => $payload['occurred_at'] ?? null,
            'old_qty' => $stockChange['old_qty'],
            'new_qty' => $stockChange['new_qty'],
        ]);

        $this->stockEventRepository->markProcessed($eventId);
    }
}
