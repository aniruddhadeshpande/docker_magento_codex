<?php

declare(strict_types=1);

namespace Training\StockNotifyQueue\Model\Event;

use Psr\Log\LoggerInterface;

class StockEventProcessor
{
    public function __construct(
        private readonly StockEventRepository $stockEventRepository,
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

        $this->logger->info('Training stock increase event stored.', [
            'event_id' => $eventId,
            'sku' => $payload['sku'],
            'qty_delta' => $payload['qty_delta'],
            'source_code' => $payload['source_code'] ?? null,
            'occurred_at' => $payload['occurred_at'] ?? null,
        ]);

        $this->stockEventRepository->markProcessed($eventId);
    }
}
