<?php

declare(strict_types=1);

namespace Training\StockNotifyQueue\Console\Command;

use DateTimeImmutable;
use Magento\Framework\Console\Cli;
use Magento\Framework\MessageQueue\PublisherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PublishStockIncrease extends Command
{
    private const TOPIC_NAME = 'training.erp.stock.increase';
    private const ARG_SKU = 'sku';
    private const ARG_QTY_DELTA = 'qty_delta';
    private const ARG_EVENT_ID = 'event_id';

    public function __construct(
        private readonly PublisherInterface $publisher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('training:erp:stock-increase');
        $this->setDescription('Publish a dummy ERP stock increase event.');
        $this->addArgument(self::ARG_SKU, InputArgument::REQUIRED, 'Product SKU.');
        $this->addArgument(self::ARG_QTY_DELTA, InputArgument::REQUIRED, 'Positive stock quantity delta.');
        $this->addArgument(self::ARG_EVENT_ID, InputArgument::REQUIRED, 'Unique ERP event id.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sku = trim((string)$input->getArgument(self::ARG_SKU));
        $qtyDelta = $this->getPositiveQuantity($input->getArgument(self::ARG_QTY_DELTA));
        $eventId = trim((string)$input->getArgument(self::ARG_EVENT_ID));

        if ($sku === '') {
            $output->writeln('<error>SKU is required.</error>');
            return Cli::RETURN_FAILURE;
        }

        if ($eventId === '') {
            $output->writeln('<error>Event ID is required.</error>');
            return Cli::RETURN_FAILURE;
        }

        if ($qtyDelta <= 0) {
            $output->writeln('<error>qty_delta must be greater than 0.</error>');
            return Cli::RETURN_FAILURE;
        }

        $payload = [
            'event_id' => $eventId,
            'sku' => $sku,
            'qty_delta' => $qtyDelta,
            'source_code' => 'default',
            'occurred_at' => (new DateTimeImmutable())->format(DATE_ATOM),
        ];
        $message = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->publisher->publish(self::TOPIC_NAME, $message);

        $output->writeln('<info>Published ERP stock increase event.</info>');
        $output->writeln($message);

        return Cli::RETURN_SUCCESS;
    }

    private function getPositiveQuantity(mixed $value): int
    {
        if (!is_scalar($value)) {
            return 0;
        }

        $normalized = trim((string)$value);
        if ($normalized === '' || !ctype_digit($normalized)) {
            return 0;
        }

        return (int)$normalized;
    }
}
