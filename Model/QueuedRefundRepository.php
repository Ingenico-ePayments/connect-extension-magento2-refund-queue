<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model;

use Ingenico\RefundQueue\Model\QueuedRefund as LegacyModel;
use Ingenico\RefundQueue\Model\QueuedRefundFactory as LegacyModelFactory;
use Ingenico\RefundQueue\Model\ResourceModel\QueuedRefund as QueuedRefundResource;
use Ingenico\RefundQueue\Model\ResourceModel\QueuedRefund\CollectionFactory;
use Ingenico\RefundQueue\Api\QueuedRefundRepositoryInterface;
use Ingenico\RefundQueue\Model\Data\QueuedRefund;
use Ingenico\RefundQueue\Model\Data\QueuedRefund\Collection;

class QueuedRefundRepository implements QueuedRefundRepositoryInterface
{
    /**
     * @var QueuedRefundResource
     */
    private $resourceModel;

    /**
     * @var LegacyModelFactory
     */
    private $legacyModelFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Marshaller
     */
    private $marshaller;

    public function __construct(
        QueuedRefundResource $resourceModel,
        LegacyModelFactory $legacyModelFactory,
        CollectionFactory $collectionFactory,
        Marshaller $marshaller
    ) {
        $this->resourceModel = $resourceModel;
        $this->legacyModelFactory = $legacyModelFactory;
        $this->collectionFactory = $collectionFactory;
        $this->marshaller = $marshaller;
    }

    public function load(int $queuedRefundId): QueuedRefund
    {
        $legacyModel = $this->legacyModelFactory->create();
        $this->resourceModel->load($legacyModel, $queuedRefundId);
        return $this->marshaller->fromLegacyModel($legacyModel);
    }

    public function save(QueuedRefund $queuedRefund): QueuedRefund
    {
        $legacyModel = $this->marshaller->toLegacyModel($queuedRefund);
        $this->resourceModel->save($legacyModel);

        return $this->marshaller->fromLegacyModel($legacyModel);
    }

    public function getQueuedRefundsByStatus(string $status): Collection
    {
        $magentoCollection = $this->collectionFactory->create();
        $magentoCollection
            ->addFilter(LegacyModel::KEY_STATUS, $status)
            ->load();
        $collection = new Collection();

        foreach ($magentoCollection as $legacyModel) {
            $collection->append($this->marshaller->fromLegacyModel($legacyModel));
        }

        return $collection;
    }
}
