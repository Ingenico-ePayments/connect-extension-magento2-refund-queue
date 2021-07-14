<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Api;

use Ingenico\RefundQueue\Model\Data\QueuedRefund;
use Ingenico\RefundQueue\Model\Data\QueuedRefund\Collection;

interface QueuedRefundRepositoryInterface
{
    /**
     * Load a QueuedRefund by it's ID
     *
     * @param int $queuedRefundId
     * @return QueuedRefund
     */
    public function load(int $queuedRefundId): QueuedRefund;

    /**
     * Save a QueuedRefund
     *
     * @param QueuedRefund $queuedRefund
     * @return QueuedRefund
     */
    public function save(QueuedRefund $queuedRefund): QueuedRefund;

    /**
     * Get an collection with queued refunds filtered by status
     *
     * @param string $status
     * @return Collection
     */
    public function getQueuedRefundsByStatus(string $status): Collection;
}
