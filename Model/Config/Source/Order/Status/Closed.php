<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model\Config\Source\Order\Status;

use Magento\Sales\Model\Config\Source\Order\Status;
use Magento\Sales\Model\Order;

class Closed extends Status
{
    protected $_stateStatuses = Order::STATE_CLOSED;
}
