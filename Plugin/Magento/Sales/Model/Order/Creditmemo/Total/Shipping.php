<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Plugin\Magento\Sales\Model\Order\Creditmemo\Total;

use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\RefundQueue\Model\ShippingAmountService;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Total\Shipping as BaseShipping;

class Shipping
{
    /**
     * @var ShippingAmountService
     */
    private $shippingAmountService;

    public function __construct(ShippingAmountService $shippingAmountService)
    {
        $this->shippingAmountService = $shippingAmountService;
    }

    public function aroundCollect(
        BaseShipping $subject,
        callable $proceed,
        Creditmemo $creditmemo
    ) {
        // Only apply to payments placed by Ingenico:
        $payment = $creditmemo->getOrder()->getPayment();
        if (!$payment instanceof OrderPaymentInterface || $payment->getMethod() !== ConfigProvider::CODE) {
            return $proceed($creditmemo);
        }

        // Temporary set the refunded amount, to include the shipping amounts
        // that are in queued refunds:
        $order = $creditmemo->getOrder();
        $originalShippingRefunded = (float) $order->getShippingRefunded();
        $originalBaseShippingRefunded = (float) $order->getBaseShippingRefunded();
        $originalShippingTaxRefunded = (float) $order->getShippingTaxRefunded();
        $originalBaseShippingTaxRefunded = (float) $order->getBaseShippingTaxRefunded();

        $refundedBaseShippingAmountInclTax = $this->shippingAmountService
            ->getPendingRefundedShippingAmount($creditmemo->getInvoice(), true, true);
        $refundedBaseShippingAmountExclTax = $this->shippingAmountService
            ->getPendingRefundedShippingAmount($creditmemo->getInvoice(), true, false);
        $refundedShippingAmountInclTax = $this->shippingAmountService
            ->getPendingRefundedShippingAmount($creditmemo->getInvoice(), false, true);
        $refundedShippingAmountExclTax = $this->shippingAmountService
            ->getPendingRefundedShippingAmount($creditmemo->getInvoice(), false, false);

        $order->setShippingRefunded($originalShippingRefunded + $refundedShippingAmountExclTax);
        $order->setBaseShippingRefunded($originalBaseShippingRefunded + $refundedBaseShippingAmountExclTax);
        $order->setShippingTaxRefunded($originalShippingTaxRefunded + ($refundedShippingAmountInclTax - $refundedShippingAmountExclTax));
        $order->setBaseShippingTaxRefunded($originalBaseShippingTaxRefunded + ($refundedBaseShippingAmountInclTax - $refundedBaseShippingAmountExclTax));

        $result = $proceed($creditmemo);

        // Restore original order:
        $order->setShippingRefunded($originalShippingRefunded);
        $order->setBaseShippingRefunded($originalBaseShippingRefunded);
        $order->setShippingTaxRefunded($originalShippingTaxRefunded);
        $order->setBaseShippingTaxRefunded($originalBaseShippingTaxRefunded);

        return $result;
    }
}
