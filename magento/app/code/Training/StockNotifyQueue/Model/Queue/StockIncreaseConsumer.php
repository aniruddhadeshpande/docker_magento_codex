<?php

declare(strict_types=1);

namespace Training\StockNotifyQueue\Model\Queue;

use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;

class StockIncreaseConsumer
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function process(string $message): void
    {
        $payload = $this->decodeMessage($message);
        $this->validatePayload($payload);

        $this->logger->info('Training stock increase event received.', [
            'event_id' => $payload['event_id'],
            'sku' => $payload['sku'],
            'qty_delta' => $payload['qty_delta'],
            'source_code' => $payload['source_code'] ?? null,
            'occurred_at' => $payload['occurred_at'] ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMessage(string $message): array
    {
        try {
            $payload = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('ERP stock increase message must be valid JSON.', 0, $exception);
        }

        if (!is_array($payload)) {
            throw new InvalidArgumentException('ERP stock increase message must decode to a JSON object.');
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validatePayload(array $payload): void
    {
        $eventId = $payload['event_id'] ?? null;
        if (!is_string($eventId) || trim($eventId) === '') {
            throw new InvalidArgumentException('ERP stock increase message requires event_id.');
        }

        $sku = $payload['sku'] ?? null;
        if (!is_string($sku) || trim($sku) === '') {
            throw new InvalidArgumentException('ERP stock increase message requires sku.');
        }

        $qtyDelta = $payload['qty_delta'] ?? null;
        if (!is_int($qtyDelta) || $qtyDelta <= 0) {
            throw new InvalidArgumentException('ERP stock increase message requires qty_delta greater than 0.');
        }
    }
}
