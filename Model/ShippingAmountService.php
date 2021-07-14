<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model;

use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Tax\Model\Config;

class ShippingAmountService
{
    /**
     * @var Config
     */
    private $taxConfig;

    public function __construct(Config $taxConfig)
    {
        $this->taxConfig = $taxConfig;
    }

    public function getPendingRefundedShippingAmount(
        Invoice $invoice,
        bool $base = true,
        bool $inclTax = true
    ): float {
        $refundedShippingAmount = 0.00;

        $order = $invoice->getOrder();
        $creditMemos = $order->getCreditmemosCollection()
            ->addFieldToFilter(Creditmemo::STATE, Creditmemo::STATE_OPEN);
        /** @var Creditmemo $creditMemo */
        foreach ($creditMemos as $creditMemo) {
            $refundedShippingAmount += $inclTax ?
                (float) ($base ? $creditMemo->getBaseShippingInclTax() : $creditMemo->getShippingInclTax()) :
                (float) ($base ? $creditMemo->getBaseShippingAmount() : $creditMemo->getShippingAmount());
        }

        return $refundedShippingAmount;
    }

    /**
     * Gets shipping amount based on invoice.
     *
     * @param Invoice $invoice
     * @return float
     */
    public function getRefundableShippingAmount(Invoice $invoice): float
    {
        $order = $invoice->getOrder();

        $pendingRefundedShippingAmount = $this->getPendingRefundedShippingAmount($invoice);

        $isShippingInclTax = $this->taxConfig->displaySalesShippingInclTax($order->getStoreId());
        if ($isShippingInclTax) {
            $amount = $order->getBaseShippingInclTax() -
                $order->getBaseShippingRefunded() -
                $order->getBaseShippingTaxRefunded();
            $amount -= $pendingRefundedShippingAmount;
        } else {
            $amount = $order->getBaseShippingAmount() - $order->getBaseShippingRefunded();
            $amount -= $pendingRefundedShippingAmount;
            $amount = min($amount, $invoice->getBaseShippingAmount());
        }

        return (float) $amount;
    }
}
