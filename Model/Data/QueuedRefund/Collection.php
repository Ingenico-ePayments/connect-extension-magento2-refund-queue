<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model\Data\QueuedRefund;

use ArrayObject;
use Ingenico\RefundQueue\Model\Data\QueuedRefund;
use InvalidArgumentException;

class Collection extends ArrayObject
{
    /**
     * @param int $index
     * @param QueuedRefund $newval
     */
    public function offsetSet($index, $newval)
    {
        if (!$newval instanceof QueuedRefund) {
            throw new InvalidArgumentException('Must be ' . QueuedRefund::class);
        }

        parent::offsetSet($index, $newval);
    }
}
