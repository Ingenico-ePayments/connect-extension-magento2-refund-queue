<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Plugin\Magento\Sales\Model\Service;

use Ingenico\RefundQueue\Api\QueuedRefundRepositoryInterface;
use Ingenico\RefundQueue\Api\RefundQueueManagementInterface;
use Ingenico\Connect\Model\ConfigProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Service\CreditmemoService as MagentoCreditmemoService;

class CreditmemoService
{
    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditMemoRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RefundQueueManagementInterface
     */
    private $refundQueueManagement;

    /**
     * @var QueuedRefundRepositoryInterface
     */
    private $queuedRefundRepository;

    public function __construct(
        CreditmemoRepositoryInterface $creditMemoRepository,
        OrderRepositoryInterface $orderRepository,
        RefundQueueManagementInterface $refundQueueManagement,
        QueuedRefundRepositoryInterface $queuedRefundRepository
    ) {
        $this->creditMemoRepository = $creditMemoRepository;
        $this->orderRepository = $orderRepository;
        $this->refundQueueManagement = $refundQueueManagement;
        $this->queuedRefundRepository = $queuedRefundRepository;
    }

    /**
     * @param MagentoCreditmemoService $subject
     * @param callable $proceed
     * @param int $id
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function aroundCancel(
        MagentoCreditmemoService $subject,
        callable $proceed,
        int $id
    ) {
        // Since Magento does not allow cancellation of credit memo's and
        // has no built-in methods to do so, this feature has been postponed for now
        // @see https://github.com/magento/magento2/issues/24573
        return $proceed($id);

        $creditMemo = $this->creditMemoRepository->get($id);

        if (!$this->isOrderPaidWithIngenico((int) $creditMemo->getOrderId())) {
            return $proceed($id);
        }

        // Cancel is only allowed if there are pending refunds in the queue.
        // Otherwise, default Magento flow applies (which means you cannot
        // cancel a credit memo, since this would effectively mean that you
        // would get the funds back from the customer that you refunded).
        if ($this->refundQueueManagement->isQueued($creditMemo)) {
            // Mark the queued refund as cancelled:
            $queuedRefund = $this->refundQueueManagement->getQueuedRefundForCreditMemo($creditMemo);
            $queuedRefund->cancel();
            $this->queuedRefundRepository->save($queuedRefund);

            // Mark the credit memo as cancelled:
            $creditMemo->setState(Creditmemo::STATE_CANCELED);
            $this->creditMemoRepository->save($creditMemo);
            return null;
        }

        return $proceed($id);
    }

    private function isOrderPaidWithIngenico(int $orderId): bool
    {
        $order = $this->orderRepository->get($orderId);
        return $order->getPayment()->getMethod() === ConfigProvider::CODE;
    }
}
