<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Cron;

use Ingenico\RefundQueue\Api\RefundQueueManagementInterface;
use Ingenico\RefundQueue\Api\QueuedRefundRepositoryInterface;
use Ingenico\RefundQueue\Model\Data\QueuedRefund;

class RefundQueue
{
    /**
     * @var QueuedRefundRepositoryInterface
     */
    private $queuedRefundRepository;

    /**
     * @var RefundQueueManagementInterface
     */
    private $refundQueueManagement;

    public function __construct(
        QueuedRefundRepositoryInterface $queuedRefundRepository,
        RefundQueueManagementInterface $refundQueueManagement
    ) {
        $this->queuedRefundRepository = $queuedRefundRepository;
        $this->refundQueueManagement = $refundQueueManagement;
    }

    public function execute(): void
    {
        $queuedRefunds = $this->queuedRefundRepository
            ->getQueuedRefundsByStatus(QueuedRefund::STATUS_PENDING);
        /** @var QueuedRefund $queuedRefund */
        foreach ($queuedRefunds as $queuedRefund) {
            $this->refundQueueManagement->processQueuedRefund($queuedRefund);
        }
    }
}
