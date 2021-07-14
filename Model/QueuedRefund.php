<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model;

use Ingenico\RefundQueue\Model\ResourceModel\QueuedRefund as QueuedRefundResource;
use Ingenico\RefundQueue\Model\Data\QueuedRefund as IngenicoQueuedRefund;
use Magento\Framework\Model\AbstractModel;

/**
 * Class RefundQueue
 * This is a decorator for the existing model provided by the refund
 * library, only for internal use. Do not instantiate directly!
 *
 * @package Ingenico\Connect\Model\Ingenico\RefundQueue
 * @internal
 */
class QueuedRefund extends AbstractModel
{
    public const KEY_PAYMENT_ID = 'payment_id';
    public const KEY_MERCHANT_ID = 'merchant_id';
    public const KEY_REFUND_REQUEST = 'refund_request';
    public const KEY_CREATION_TIME = 'creation_time';
    public const KEY_STATUS = 'status';
    public const KEY_ID = 'id';
    public const KEY_UPDATE_TIME = 'update_time';
    public const KEY_META_DATA = 'meta_data';

    public function createFrom(IngenicoQueuedRefund $queuedRefund)
    {
        $this->setData([
            self::KEY_PAYMENT_ID => $queuedRefund->getPaymentId(),
            self::KEY_MERCHANT_ID => $queuedRefund->getMerchantId(),
            self::KEY_REFUND_REQUEST => $queuedRefund->getRefundRequest(),
            self::KEY_CREATION_TIME => $queuedRefund->getCreationTime(),
            self::KEY_STATUS => $queuedRefund->getStatus(),
            self::KEY_ID => $queuedRefund->getId(),
            self::KEY_UPDATE_TIME => $queuedRefund->getUpdateTime(),
            self::KEY_META_DATA => $queuedRefund->getMetaData(),
        ]);
    }

    protected function _construct()
    {
        $this->_init(QueuedRefundResource::class);
    }
}
