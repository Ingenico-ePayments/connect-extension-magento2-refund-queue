<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model;

use Ingenico\RefundQueue\Api\QueuedRefundRepositoryInterface;
use Ingenico\RefundQueue\Api\RefundQueueManagementInterface;
use Ingenico\RefundQueue\Model\Data\QueuedRefund;
use Ingenico\RefundQueue\Model\QueuedRefund as LegacyQueuedRefundModel;
use Ingenico\RefundQueue\Model\RefundProcessor;
use Ingenico\RefundQueue\Model\RefundQueueService;
use Ingenico\RefundQueue\Model\ResourceModel\QueuedRefund\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;

class RefundQueueManagement implements RefundQueueManagementInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Marshaller
     */
    private $marshaller;

    /**
     * @var array for caching purposes
     */
    private $cache = [];

    /**
     * @var RefundQueueServiceBuilder
     */
    private $refundQueueServiceBuilder;

    /**
     * @var QueuedRefundRepositoryInterface
     */
    private $queuedRefundRepository;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditMemoRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var array For internal caching purposes
     */
    private $refundQueueServiceByStoreId = [];

    public function __construct(
        CollectionFactory $collectionFactory,
        Marshaller $marshaller,
        RefundQueueServiceBuilder $refundQueueServiceBuilder,
        QueuedRefundRepositoryInterface $queuedRefundRepository,
        CreditmemoRepositoryInterface $creditMemoRepository,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->marshaller = $marshaller;
        $this->refundQueueServiceBuilder = $refundQueueServiceBuilder;
        $this->queuedRefundRepository = $queuedRefundRepository;
        $this->creditMemoRepository = $creditMemoRepository;
        $this->orderRepository = $orderRepository;
    }

    public function isQueued(CreditmemoInterface $creditMemo): bool
    {
        try {
            $queuedRefund = $this->getQueuedRefundForCreditMemo($creditMemo);
            return $queuedRefund->getStatus() === QueuedRefund::STATUS_PENDING;
        } catch (LocalizedException $exception) {
            return false;
        }
    }

    public function getQueuedRefundForCreditMemo(CreditmemoInterface $creditMemo): QueuedRefund
    {
        if (!array_key_exists($creditMemo->getEntityId(), $this->cache)) {
            $collection = $this->collectionFactory->create();
            $collection
                ->addFieldToFilter(LegacyQueuedRefundModel::KEY_STATUS, QueuedRefund::STATUS_PENDING)
                ->addFieldToFilter(
                    LegacyQueuedRefundModel::KEY_META_DATA,
                    ['like' => sprintf('%%"creditmemo_id":"%1$d"%%', $creditMemo->getEntityId())]
                );

            if ($collection->getSize() === 0) {
                throw new NoSuchEntityException(
                    __('No queued refund found for credit memo %1', $creditMemo->getEntityId())
                );
            }

            if ($collection->getSize() > 1) {
                throw new LocalizedException(
                    __('Multiple queued refund found for credit memo %1', $creditMemo->getEntityId())
                );
            }

            $this->cache[$creditMemo->getEntityId()] = $this->marshaller->fromLegacyModel($collection->getFirstItem());
        }

        return $this->cache[$creditMemo->getEntityId()];
    }

    public function processQueuedRefund(QueuedRefund $queuedRefund, ?CreditmemoInterface $creditMemo = null): void
    {
        if (!$creditMemo) {
            $creditMemoId = (int) $queuedRefund->getMetaData()[RefundProcessor::KEY_CREDITMEMO_ID];
            /** @var Creditmemo $creditMemo */
            $creditMemo = $this->creditMemoRepository->get($creditMemoId);
        }
        $refundQueueService = $this->getRefundQueueService((int) $creditMemo->getStoreId());
        $refundQueueService->processQueuedRefund($queuedRefund);
        $creditMemo->addComment(
            __('Updated refund queue. Status: %1', $queuedRefund->getStatus())
        );
        // Persist everything:
        $this->creditMemoRepository->save($creditMemo);
        $this->queuedRefundRepository->save($queuedRefund);
        $this->orderRepository->save($creditMemo->getOrder());
    }

    /**
     * Since Magento is a multi-store setup, we need to get the
     * store ID of each specific credit memo:
     *
     * @param int $storeId
     * @return RefundQueueService
     */
    private function getRefundQueueService(int $storeId): RefundQueueService
    {
        if (!array_key_exists($storeId, $this->refundQueueServiceByStoreId)) {
            $this->refundQueueServiceByStoreId[$storeId] =
                $this->refundQueueServiceBuilder->build($storeId);
        }

        return $this->refundQueueServiceByStoreId[$storeId];
    }
}
