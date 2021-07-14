<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model;

use DateTime;
use Ingenico\RefundQueue\Model\Data\QueuedRefund;
use Ingenico\RefundQueue\Model\Data\QueuedRefundFactory as QueuedRefundFactory;
use Ingenico\RefundQueue\Model\QueuedRefund as LegacyModel;
use Ingenico\RefundQueue\Model\QueuedRefundFactory as LegacyModelFactory;
use Magento\Framework\Intl\DateTimeFactory;

class Marshaller
{
    /**
     * @var LegacyModelFactory
     */
    private $legacyModelFactory;

    /**
     * @var QueuedRefundFactory
     */
    private $queuedRefundFactory;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    public function __construct(
        LegacyModelFactory $legacyModelFactory,
        QueuedRefundFactory $queuedRefundFactory,
        DateTimeFactory $dateTimeFactory
    ) {
        $this->legacyModelFactory = $legacyModelFactory;
        $this->queuedRefundFactory = $queuedRefundFactory;
        $this->dateTimeFactory = $dateTimeFactory;
    }

    public function fromLegacyModel(LegacyModel $queuedRefund): QueuedRefund
    {
        return $this->queuedRefundFactory->create([
            'paymentId' => $queuedRefund->getData(LegacyModel::KEY_PAYMENT_ID),
            'merchantId' => $queuedRefund->getData(LegacyModel::KEY_MERCHANT_ID),
            'refundRequest' => $queuedRefund->getData(LegacyModel::KEY_REFUND_REQUEST),
            'creationTime' => $this->getDateTimeObject($queuedRefund, LegacyModel::KEY_CREATION_TIME),
            'status' => $queuedRefund->getData(LegacyModel::KEY_STATUS),
            'id' => $queuedRefund->getData(LegacyModel::KEY_ID),
            'updateTime' => $this->getDateTimeObject($queuedRefund, LegacyModel::KEY_UPDATE_TIME),
            'metaData' => $queuedRefund->getData(LegacyModel::KEY_META_DATA),
        ]);
    }

    public function toLegacyModel(QueuedRefund $queuedRefund): LegacyModel
    {
        $legacyModel = $this->legacyModelFactory->create();
        $legacyModel->setData([
            LegacyModel::KEY_PAYMENT_ID => $queuedRefund->getPaymentId(),
            LegacyModel::KEY_MERCHANT_ID => $queuedRefund->getMerchantId(),
            LegacyModel::KEY_REFUND_REQUEST => $queuedRefund->getRefundRequest(),
            LegacyModel::KEY_CREATION_TIME => $queuedRefund->getCreationTime(),
            LegacyModel::KEY_STATUS => $queuedRefund->getStatus(),
            LegacyModel::KEY_ID => $queuedRefund->getId(),
            LegacyModel::KEY_UPDATE_TIME => $queuedRefund->getUpdateTime(),
            LegacyModel::KEY_META_DATA => $queuedRefund->getMetaData(),
        ]);

        return $legacyModel;
    }

    private function getDateTimeObject(LegacyModel $legacyModel, string $key)
    {
        $value = $legacyModel->getData($key);

        if ($value !== null && !$value instanceof DateTime) {
            return $this->dateTimeFactory->create($value);
        }

        return $value;
    }
}
