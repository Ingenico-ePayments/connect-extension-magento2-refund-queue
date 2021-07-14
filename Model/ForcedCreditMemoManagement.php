<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model;

use Ingenico\RefundQueue\Api\RefundQueueManagementInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory;

class ForcedCreditMemoManagement
{
    public const KEY_IS_PROCESSED = 'ingenico_set_forced_creditmemo_processed';

    /**
     * @var RefundQueueManagementInterface
     */
    private $refundQueueManagement;
    /**
     * @var CollectionFactory
     */
    private $creditMemoCollectionFactory;

    public function __construct(
        RefundQueueManagementInterface $refundQueueManagement,
        CollectionFactory $creditMemoCollectionFactory
    ) {
        $this->refundQueueManagement = $refundQueueManagement;
        $this->creditMemoCollectionFactory = $creditMemoCollectionFactory;
    }

    public function setFlag(Order $order): void
    {
        $totalRefunded = (string) $order->getBaseTotalRefunded();

        if (!$order->getId()) {
            return;
        }

        // Check if there are creditmemos in the refund queue:
        $creditMemos = $this->creditMemoCollectionFactory->create()
            ->setOrderFilter($order)
            ->addFieldToFilter(Creditmemo::STATE, Creditmemo::STATE_OPEN);

        /** @var Creditmemo $creditMemo */
        foreach ($creditMemos as $creditMemo) {
            if ($this->refundQueueManagement->isQueued($creditMemo)) {
                $totalRefunded = bcadd($totalRefunded, (string) $creditMemo->getBaseGrandTotal(), 4);
            }
        }

        if (bccomp($totalRefunded, (string) $order->getBaseGrandTotal(), 4) === 0) {
            /** @see \Magento\Sales\Model\Order::canCreditmemo() */
            $order->setForcedCanCreditmemo(false);
            $order->setData(self::KEY_IS_PROCESSED, true);
        }
    }
}
