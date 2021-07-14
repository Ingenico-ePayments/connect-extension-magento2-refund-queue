<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model\ResourceModel;

use DateTime;
use Ingenico\RefundQueue\Model\QueuedRefund as QueuedRefundLegacyModel;
use Ingenico\Connect\Sdk\Domain\Refund\RefundRequest;
use Ingenico\Connect\Sdk\Domain\Refund\RefundRequestFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

class QueuedRefund extends AbstractDb
{
    public const TABLE_NAME = 'ingenico_queued_refunds';

    /**
     * @var RefundRequestFactory
     */
    private $refundRequestFactory;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    public function __construct(
        RefundRequestFactory $refundRequestFactory,
        DateTimeFactory $dateTimeFactory,
        Context $context,
        $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->refundRequestFactory = $refundRequestFactory;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    // Set serializable fields so we can overwrite them in our resource model:
    protected $_serializableFields = [
        QueuedRefundLegacyModel::KEY_REFUND_REQUEST => [null, null],
        QueuedRefundLegacyModel::KEY_META_DATA => [null, null],
        QueuedRefundLegacyModel::KEY_CREATION_TIME => [null, null],
        QueuedRefundLegacyModel::KEY_UPDATE_TIME => [null, null],
    ];

    protected function _construct()
    {
        $this->_init(
            self::TABLE_NAME,
            QueuedRefundLegacyModel::KEY_ID
        );
    }

    protected function _serializeField(DataObject $object, $field, $defaultValue = null, $unsetEmpty = false)
    {
        if (!$object->getData($field)) {
            return $this;
        }

        if ($field === QueuedRefundLegacyModel::KEY_REFUND_REQUEST) {
            /** @var RefundRequest $refundRequest */
            $refundRequest = $object->getData($field);
            // Use serializer as implemented by the SDK:
            $object->setData(
                QueuedRefundLegacyModel::KEY_REFUND_REQUEST,
                $refundRequest->toJson()
            );
            return $this;
        }

        if ($object->getData($field) instanceof DateTime) {
            $object->setData($field, $object->getData($field)->format('Y-m-d H:i:s'));
            return $this;
        }

        return parent::_serializeField($object, $field, $defaultValue, $unsetEmpty);
    }

    protected function _unserializeField(DataObject $object, $field, $defaultValue = null)
    {
        if ($field === QueuedRefundLegacyModel::KEY_REFUND_REQUEST) {
            $refundRequest = $this->refundRequestFactory->create();
            $object->setData(
                QueuedRefundLegacyModel::KEY_REFUND_REQUEST,
                $refundRequest->fromJson($object->getData($field))
            );
            return;
        }

        $dateTimeFields = [
            QueuedRefundLegacyModel::KEY_CREATION_TIME,
            QueuedRefundLegacyModel::KEY_UPDATE_TIME,
        ];
        if (in_array($field, $dateTimeFields) && $object->getData($field)) {
            $object->setData(
                $field,
                $this->dateTimeFactory->create($object->getData($field))
            );
            return;
        }

        parent::_unserializeField($object, $field, $defaultValue);
    }
}
