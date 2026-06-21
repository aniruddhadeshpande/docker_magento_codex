<?php

declare(strict_types=1);

namespace Training\StockNotifyQueue\Model\Inventory;

use InvalidArgumentException;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Psr\Log\LoggerInterface;

class StockUpdater
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly GetSourceItemsBySkuInterface $getSourceItemsBySku,
        private readonly SourceItemsSaveInterface $sourceItemsSave,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array{old_qty: float, new_qty: float}
     */
    public function increase(string $sku, int $qtyDelta, string $sourceCode): array
    {
        if ($qtyDelta <= 0) {
            throw new InvalidArgumentException('Quantity delta must be greater than 0.');
        }

        $this->productRepository->get($sku);

        $sourceItem = $this->getSourceItem($sku, $sourceCode);
        $oldQty = (float)$sourceItem->getQuantity();
        $newQty = $oldQty + $qtyDelta;

        $sourceItem->setQuantity($newQty);
        if ($newQty > 0) {
            $sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
        }

        $this->sourceItemsSave->execute([$sourceItem]);

        $this->logger->info('Training stock quantity increased.', [
            'sku' => $sku,
            'source_code' => $sourceCode,
            'qty_delta' => $qtyDelta,
            'old_qty' => $oldQty,
            'new_qty' => $newQty,
        ]);

        return [
            'old_qty' => $oldQty,
            'new_qty' => $newQty,
        ];
    }

    private function getSourceItem(string $sku, string $sourceCode): SourceItemInterface
    {
        foreach ($this->getSourceItemsBySku->execute($sku) as $sourceItem) {
            if ($sourceItem->getSourceCode() === $sourceCode) {
                return $sourceItem;
            }
        }

        throw NoSuchEntityException::singleField('source_code', $sourceCode);
    }
}
