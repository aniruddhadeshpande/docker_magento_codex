<?php

declare(strict_types=1);

namespace Training\StockNotifyQueue\Model\Event;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\DuplicateException;

class StockEventRepository
{
    private const TABLE_NAME = 'training_erp_stock_event';
    private const STATUS_RECEIVED = 'received';
    private const STATUS_PROCESSING = 'processing';
    private const STATUS_PROCESSED = 'processed';

    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function insertReceived(array $payload): bool
    {
        $connection = $this->resourceConnection->getConnection();

        try {
            $connection->insert($this->getTableName(), [
                'event_id' => $payload['event_id'],
                'sku' => $payload['sku'],
                'qty_delta' => $payload['qty_delta'],
                'source_code' => $payload['source_code'] ?? 'default',
                'status' => self::STATUS_RECEIVED,
            ]);
        } catch (DuplicateException) {
            return false;
        }

        return true;
    }

    public function markProcessing(string $eventId): void
    {
        $this->updateStatus($eventId, self::STATUS_PROCESSING);
    }

    public function markProcessed(string $eventId): void
    {
        $this->updateStatus($eventId, self::STATUS_PROCESSED, [
            'processed_at' => gmdate('Y-m-d H:i:s'),
            'error_message' => null,
        ]);
    }

    /**
     * @param array<string, mixed> $extraData
     */
    private function updateStatus(string $eventId, string $status, array $extraData = []): void
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->update(
            $this->getTableName(),
            array_merge(['status' => $status], $extraData),
            ['event_id = ?' => $eventId]
        );
    }

    private function getTableName(): string
    {
        return $this->resourceConnection->getTableName(self::TABLE_NAME);
    }
}
