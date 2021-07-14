<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model\ResourceModel\QueuedRefund;

use Ingenico\RefundQueue\Model\QueuedRefund;
use Ingenico\RefundQueue\Model\ResourceModel\QueuedRefund as QueuedRefundResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            QueuedRefund::class,
            QueuedRefundResource::class
        );
    }

    protected function _afterLoad()
    {
        foreach ($this->_items as $object) {
            $this->getResource()->unserializeFields($object);
        }

        return parent::_afterLoad();
    }
}
