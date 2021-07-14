<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Plugin\Magento\Sales\Model\ResourceModel\Order\Handler;

use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\RefundQueue\Model\Config;
use Ingenico\RefundQueue\Model\ForcedCreditMemoManagement;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Handler\State as BaseState;

class State
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var ForcedCreditMemoManagement
     */
    private $forcedCreditMemoManagement;

    public function __construct(
        Config $config,
        ForcedCreditMemoManagement $forcedCreditMemoManagement
    ) {
        $this->config = $config;
        $this->forcedCreditMemoManagement = $forcedCreditMemoManagement;
    }

    public function beforeCheck(
        BaseState $subject,
        Order $order
    ) {
        $this->forcedCreditMemoManagement->setFlag($order);
    }

    public function afterCheck(
        BaseState $subject,
        $returnValue,
        Order $order
    ) {
        if ($order->getPayment()->getMethod() !== ConfigProvider::CODE) {
            return $returnValue;
        }

        if ($this->hasQueuedRefunds($order)) {
            $this->updateOrderStatus($order);
        } else {
            $this->restoreOrderStatus($order);
        }

        return $returnValue;
    }

    private function updateOrderStatus(Order $order)
    {
        switch ($order->getState()) {
            case Order::STATE_PROCESSING:
                $order->setStatus($this->config->getQueuedRefundProcessingStatus());
                break;
            case Order::STATE_COMPLETE:
                $order->setStatus($this->config->getQueuedRefundCompleteStatus());
                break;
            case Order::STATE_CLOSED:
                $order->setStatus($this->config->getQueuedRefundClosedStatus());
                break;
        }
    }

    private function restoreOrderStatus(Order $order)
    {
        switch ($order->getStatus()) {
            case $this->config->getQueuedRefundProcessingStatus():
                $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
                break;
            case $this->config->getQueuedRefundCompleteStatus():
                $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_COMPLETE));
                break;
            case $this->config->getQueuedRefundClosedStatus():
                $order->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_CLOSED));
                break;
        }
    }

    private function hasQueuedRefunds(Order $order): bool
    {
        if ($creditMemos = $order->getCreditmemosCollection()) {
            return $creditMemos
                    ->addFieldToFilter(Creditmemo::STATE, Creditmemo::STATE_OPEN)
                    ->getTotalCount() > 0;
        }

        return false;
    }
}
