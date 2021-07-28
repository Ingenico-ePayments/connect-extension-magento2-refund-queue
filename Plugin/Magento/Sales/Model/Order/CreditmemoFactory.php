<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Plugin\Magento\Sales\Model\Order;

use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\RefundQueue\Model\ShippingAmountService;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\CreditmemoFactory as BaseCreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;

class CreditmemoFactory
{
    /**
     * @var ShippingAmountService
     */
    private $shippingAmountService;

    public function __construct(ShippingAmountService $shippingAmountService)
    {
        $this->shippingAmountService = $shippingAmountService;
    }

    public function beforeCreateByInvoice(
        BaseCreditmemoFactory $subject,
        Invoice $invoice,
        array $data = []
    ): array {
        // Only apply to payments placed by Ingenico:
        $payment = $invoice->getOrder()->getPayment();
        if (!$payment instanceof OrderPaymentInterface || $payment->getMethod() !== ConfigProvider::CODE) {
            return [$invoice, $data];
        }

        if (!isset($data['shipping_amount'])) {
            // Check if there are pending credit memo's.
            $data['shipping_amount'] = $this->shippingAmountService->getRefundableShippingAmount($invoice);
        }

        return [$invoice, $data];
    }
}
