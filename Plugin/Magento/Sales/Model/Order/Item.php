<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Plugin\Magento\Sales\Model\Order;

use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\RefundQueue\Api\RefundQueueManagementInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Item as BaseOrderItem;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory;

class Item
{
    public const IS_BEING_REGISTERED = 'is_being_registered';

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

    public function afterGetQtyRefunded(
        BaseOrderItem $subject,
        $qtyRefunded
    ) {
        if ($subject->getData(self::IS_BEING_REGISTERED) === true) {
            return $qtyRefunded;
        }

        if (!$subject->getOrder()) {
            return $qtyRefunded;
        }

        if (!$subject->getOrder()->getId()) {
            return $qtyRefunded;
        }

        if ($subject->getOrder()->getPayment()->getMethod() !== ConfigProvider::CODE) {
            return $qtyRefunded;
        }

        if ((float) $qtyRefunded == 0) {
            $qtyRefunded = 0.0;
            // Check if there are items queued:
            $creditMemos = $this->creditMemoCollectionFactory->create()
                ->setOrderFilter($subject->getOrder())
                ->addFieldToFilter(Creditmemo::STATE, Creditmemo::STATE_OPEN);
            /** @var Creditmemo $creditMemo */
            foreach ($creditMemos as $creditMemo) {
                if ($this->refundQueueManagement->isQueued($creditMemo)) {
                    // All items in this credit memo are in the refund queue.
                    foreach ($creditMemo->getItems() as $creditMemoItem) {
                        if ($creditMemoItem->getOrderItemId() === $subject->getId()) {
                            $qtyRefunded += $creditMemoItem->getQty();
                        }
                    }
                }
            }
        }

        return $qtyRefunded;
    }
}
