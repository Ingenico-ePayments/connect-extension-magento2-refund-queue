<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Api;

use Ingenico\RefundQueue\Model\Data\QueuedRefund;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\CreditmemoInterface;

interface RefundQueueManagementInterface
{
    /**
     * Check the refund queue to see if this credit memo has a pending
     * state in the refund queue. It will also return false if no queued
     * item is found.
     *
     * @param CreditmemoInterface $creditMemo
     * @return bool
     */
    public function isQueued(CreditmemoInterface $creditMemo): bool;

    /**
     * Get the matching queued refund for this credit memo.
     *
     * @param CreditmemoInterface $creditMemo
     * @return QueuedRefund
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getQueuedRefundForCreditMemo(CreditmemoInterface $creditMemo): QueuedRefund;

    /**
     * Process a queued refund. This means that an API call is made for
     * this queued refund and processed accordingly.
     *
     * @param QueuedRefund $queuedRefund
     * @param CreditmemoInterface|null $creditMemo
     */
    public function processQueuedRefund(QueuedRefund $queuedRefund, ?CreditmemoInterface $creditMemo = null): void;
}
