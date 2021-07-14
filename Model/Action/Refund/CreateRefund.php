<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model\Action\Refund;

use Ingenico\Connect\Model\Ingenico\Action\Refund\AbstractRefundAction;
use Ingenico\RefundQueue\Model\ForcedCreditMemoManagement;
use Ingenico\RefundQueue\Model\RefundProcessor;
use Ingenico\RefundQueue\Model\RefundQueueServiceBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Refund\RefundRequestBuilder;
use Ingenico\Connect\Sdk\Domain\Refund\RefundRequest;
use Ingenico\RefundQueue\Api\QueuedRefundRepositoryInterface;
use Ingenico\RefundQueue\Model\Data\QueuedRefundFactory;
use LogicException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Ingenico\RefundQueue\Model\Config;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;

/**
 * Class CreateRefund
 *
 * @package Ingenico\Connect\Model\Ingenico\Action\Refund
 */
class CreateRefund extends AbstractRefundAction
{
    /**
     * @var RefundRequestBuilder
     */
    private $refundRequestBuilder;

    /**
     * @var QueuedRefundRepositoryInterface
     */
    private $queuedRefundRepository;

    /**
     * @var QueuedRefundFactory
     */
    private $queuedRefundFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var RefundRequest
     */
    private $request;

    /**
     * @var string
     */
    private $paymentId;

    /**
     * @var RefundQueueServiceBuilder
     */
    private $refundQueueServiceBuilder;

    /**
     * @var ForcedCreditMemoManagement
     */
    private $forcedCreditMemoManagement;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        RefundRequestBuilder $refundRequestBuilder,
        Config $config,
        QueuedRefundRepositoryInterface $queuedRefundRepository,
        QueuedRefundFactory $queuedRefundFactory,
        RefundQueueServiceBuilder $refundQueueServiceBuilder,
        ForcedCreditMemoManagement $forcedCreditMemoManagement
    ) {
        parent::__construct($orderRepository, $creditmemoRepository);

        $this->refundRequestBuilder = $refundRequestBuilder;
        $this->queuedRefundRepository = $queuedRefundRepository;
        $this->queuedRefundFactory = $queuedRefundFactory;
        $this->config = $config;
        $this->refundQueueServiceBuilder = $refundQueueServiceBuilder;
        $this->forcedCreditMemoManagement = $forcedCreditMemoManagement;
    }

    protected function performRefundAction(OrderInterface $order, CreditmemoInterface $creditMemo)
    {
        $payment = $order->getPayment();
        $amount = $creditMemo->getBaseGrandTotal();
        $this->validateAmount($order, $amount);
        $this->paymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);
        $this->request = $this->refundRequestBuilder->build($order, (float) $amount);
        $creditMemo->setState(Creditmemo::STATE_OPEN);
    }

    protected function performPostRefundAction(OrderInterface $order, Creditmemo $creditMemo)
    {
        // Add the refund to the queue:
        $queuedRefund = $this->queuedRefundFactory->create([
            'paymentId' => $this->paymentId,
            'merchantId' => $this->config->getMerchantId($order->getStoreId()),
            'refundRequest' => $this->request,
            'metaData' => [
                RefundProcessor::KEY_CREDITMEMO_ID => $creditMemo->getEntityId(),
            ],
        ]);
        $this->queuedRefundRepository->save($queuedRefund);

        // Try to immediately process it:
        $isMutated = $this->refundQueueServiceBuilder
            ->build((int) $order->getStoreId())
            ->processQueuedRefund($queuedRefund);

        if (!$isMutated) {
            $comment = 'The payment is not yet refundable; a refund request has been added to the queue.';
            $creditMemo->addComment(
                __($comment)
            );

            // Also add a comment to the order:
            if ($order instanceof Order) {
                $order->addCommentToStatusHistory($comment);
            }
        } else {
            $this->queuedRefundRepository->save($queuedRefund);
        }

        // Set forced credit memo flag:
        $this->forcedCreditMemoManagement->setFlag($order);
        $this->persist($order, $creditMemo);
    }

    /**
     * @param OrderInterface $order
     * @param float|null $amount
     * @throws LocalizedException
     */
    private function validateAmount(OrderInterface $order, ?float $amount)
    {
        if (!$order instanceof Order) {
            throw new LogicException('Order must be instance of ' . Order::class);
        }

        if ((float) $amount === 0.00) {
            throw new LocalizedException(
                __(
                    'Credit memo can not be created. Amount is %1',
                    strip_tags($order->formatPrice(0.00))
                )
            );
        }

        $creditMemos = $order
            ->getCreditmemosCollection()
            ->addFilter(Creditmemo::STATE, Creditmemo::STATE_OPEN);
        $pendingAmount = 0;
        /** @var Creditmemo $creditMemo */
        foreach ($creditMemos as $creditMemo) {
            $pendingAmount += $creditMemo->getBaseGrandTotal();
        }

        $maxAllowedAmount = $order->getBaseGrandTotal() - $pendingAmount;
        if (bccomp((string) $amount, (string) $maxAllowedAmount, 4) > 0) {
            throw new LocalizedException(
                __(
                    'Credit memo can not be created. Maximum refundable amount is %1',
                    strip_tags($order->formatPrice($maxAllowedAmount))
                )
            );
        }
    }
}
