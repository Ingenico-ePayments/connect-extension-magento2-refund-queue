<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Plugin\Magento\Sales\Model\Order\Creditmemo;

use Ingenico\RefundQueue\Plugin\Magento\Sales\Model\Order\Item as BaseOrderItem;
use Magento\Sales\Model\Order\Creditmemo\Item as BaseCreditmemoItem;

class Item
{
    public function beforeRegister(
        BaseCreditmemoItem $subject
    ) {
        $subject->getOrderItem()->setData(BaseOrderItem::IS_BEING_REGISTERED, true);
    }
}
