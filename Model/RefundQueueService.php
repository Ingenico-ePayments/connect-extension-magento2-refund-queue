<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model;

use Ingenico\RefundQueue\Api\QueuedRefundRepositoryInterface;
use Ingenico\RefundQueue\Api\RefundProcessorInterface;
use Ingenico\RefundQueue\Model\Data\QueuedRefund;
use Throwable;

class RefundQueueService
{
    /**
     * @var QueuedRefundRepositoryInterface
     */
    private $queuedRefundRepository;

    /**
     * @var PaymentStatusService
     */
    private $paymentStatusService;

    /**
     * @var RefundProcessorInterface
     */
    private $refundProcessor;

    public function __construct(
        QueuedRefundRepositoryInterface $queuedRefundRepository,
        RefundProcessorInterface $refundProcessor,
        PaymentStatusService $paymentStatusService
    ) {
        $this->queuedRefundRepository = $queuedRefundRepository;
        $this->refundProcessor = $refundProcessor;
        $this->paymentStatusService = $paymentStatusService;
    }

    /**
     * Process all refunds that are in the queue with status "PENDING"
     */
    public function processRefundQueue(): void
    {
        $queuedRefunds = $this
            ->queuedRefundRepository
            ->getQueuedRefundsByStatus(QueuedRefund::STATUS_PENDING);

        /** @var QueuedRefund $queuedRefund */
        foreach ($queuedRefunds as $queuedRefund) {
            if (!$this->processQueuedRefund($queuedRefund)) {
                continue;
            }

            $this->queuedRefundRepository->save($queuedRefund);
        }
    }

    /**
     * Process a single queued refund.
     *
     * @param QueuedRefund $queuedRefund
     * @return bool TRUE on mutations (success and failure), FALSE on non-refundable
     */
    public function processQueuedRefund(QueuedRefund $queuedRefund): bool
    {
        try {
            if (!$this->isRefundable($queuedRefund)) {
                return false;
            }

            // Process by external implementor:
            $this->refundProcessor->process(
                $queuedRefund->getMerchantId(),
                $queuedRefund->getPaymentId(),
                $queuedRefund->getRefundRequest(),
                $queuedRefund->getMetaData(),
            );
            $queuedRefund->process();
        } catch (Throwable $exception) {
            if (!$this->refundProcessor->isRetryAllowed()) {
                $queuedRefund->fail();
            } else {
                $queuedRefund->updateTime();
            }
        }

        return true;
    }

    private function isRefundable(QueuedRefund $queuedRefund): bool
    {
        return $this->paymentStatusService->isRefundable(
            $queuedRefund->getPaymentId(),
            $queuedRefund->getMerchantId(),
        );
    }
}
