<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Plugin\Magento\Sales\Controller\Adminhtml\Order;

use Exception;
use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\RefundQueue\Api\RefundQueueManagementInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\View as ViewController;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;

class View
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RefundQueueManagementInterface
     */
    private $refundQueueManagement;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        RefundQueueManagementInterface $refundQueueManagement
    ) {
        $this->orderRepository = $orderRepository;
        $this->refundQueueManagement = $refundQueueManagement;
    }

    public function beforeExecute(ViewController $subject)
    {
        $id = $subject->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($id);

        $payment = $order->getPayment();
        if (!$payment instanceof OrderPaymentInterface || $payment->getMethod() !== ConfigProvider::CODE) {
            return null;
        }

        try {
            $this->updateRefundStatus($order);
        } catch (Exception $exception) {
            // An exception should never break the flow of viewing an order
            return null;
        }

        return null;
    }

    /**
     * @param OrderInterface $order
     * @throws LocalizedException
     */
    private function updateRefundStatus(OrderInterface $order)
    {
        if ($order instanceof Order) {
            $creditMemos = $order->getCreditmemosCollection()
                ->addFieldToFilter(Creditmemo::STATE, Creditmemo::STATE_OPEN);
            foreach ($creditMemos as $creditMemo) {
                if ($this->refundQueueManagement->isQueued($creditMemo)) {
                    $queuedRefund = $this->refundQueueManagement->getQueuedRefundForCreditMemo($creditMemo);
                    $this->refundQueueManagement->processQueuedRefund($queuedRefund, $creditMemo);
                }
            }
        }
    }
}
