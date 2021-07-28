<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Observer;

use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\RefundQueue\Model\ForcedCreditMemoManagement;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;

class SetForcedCreditMemo implements ObserverInterface
{
    /**
     * @var ForcedCreditMemoManagement
     */
    private $forcedCreditMemoManagement;

    public function __construct(
        ForcedCreditMemoManagement $forcedCreditMemoManagement
    ) {
        $this->forcedCreditMemoManagement = $forcedCreditMemoManagement;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');

        if (!$order instanceof Order) {
            return;
        }

        $payment = $order->getPayment();
        if (!$payment instanceof OrderPaymentInterface || $payment->getMethod() !== ConfigProvider::CODE ||
            $order->getData(ForcedCreditMemoManagement::KEY_IS_PROCESSED) === true ||
            !$order->canCreditmemo()
        ) {
            return;
        }

        $this->forcedCreditMemoManagement->setFlag($order);
    }
}
