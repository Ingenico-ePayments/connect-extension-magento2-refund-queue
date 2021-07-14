<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Test\Integration\Model\Method;

// phpcs:disable Generic.Files.LineLength.TooLong

use Ingenico\RefundQueue\Cron\RefundQueue;
use Ingenico\RefundQueue\Model\RefundProcessor;
use Ingenico\Connect\Test\Integration\Fixture\Order as OrderFixture;
use Ingenico\Connect\Test\Integration\Model\Method\AbstractRefundTest;
use Ingenico\Connect\Test\Listener as TestListener;
use Ingenico\RefundQueue\Api\QueuedRefundRepositoryInterface;
use Ingenico\RefundQueue\Model\Data\QueuedRefund;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;

/**
 * These are the integration tests for refunds.
 * All refund flows are tested with a VISA mock.
 *
 * @package Ingenico\Connect\Test\Integration\Model\Method
 */
class RefundTest extends AbstractRefundTest
{
    protected $paymentProduct = 'visa';
    protected $paymentLabel = 'Visa';

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store ingenico_epayments/checkout/inline_payments 1
     * @magentoConfigFixture current_store ingenico_epayments/settings/merchant_id 12345
     * @magentoConfigFixture current_store ingenico_epayments/settings/queued_refund_status_processing processing_refund_queued
     * @magentoConfigFixture current_store ingenico_epayments/settings/queued_refund_status_complete complete_refund_queued
     * @magentoConfigFixture current_store ingenico_epayments/settings/queued_refund_status_closed closed_refund_queued
     * @magentoConfigFixture current_store ingenico_epayments/captures/capture_mode direct
     * @magentoConfigFixture current_store ingenico_epayments/settings/log_all_requests 1
     * @magentoConfigFixture current_store ingenico_epayments/webhook/webhooks_key_id WEBHOOKS_KEY_ID
     * @magentoConfigFixture current_store ingenico_epayments/webhook/webhooks_secret_key WEBHOOKS_SECRET_KEY
     */
    public function testVisaDefaultRefundFlowWithRefundQueue()
    {
        $this->createRefundQueueOrderStatuses();
        TestListener::describe('Place a successful order followed by a refund');

        // Setup:
        TestListener::step('Place Order');
        $order = $this->placeOrder();
        $this->webhookFixture->triggerPaymentWebhook('payment.capture_requested', $order);
        $this->assertOrderIsProcessing($order);

        TestListener::step('Create Credit Memo');
        $order = $this->refundOrder(
            $order,
            'getPayment:payment.capture_requested'
        );

        // There should be an open credit memo, but there should be no change on the order:
        $creditMemo = $this->getCreditMemo($order);
        $this->assertOrderStatus($order, 'closed_refund_queued', Order::STATE_CLOSED);
        $this->assertSameAndReport('Refunded Amount', 0.00, $order->getTotalRefunded());
        $this->assertSameAndReport('Base Refunded Amount', 0.00, $order->getBaseTotalRefunded());
        $this->assertSameAndReport('Creditmemo Status', Creditmemo::STATE_OPEN, $creditMemo->getState());

        // Check if there is a queued refund for this:
        /** @var QueuedRefundRepositoryInterface $queuedRefundRepository */
        $queuedRefundRepository = $this->objectManager->get(QueuedRefundRepositoryInterface::class);
        $queuedRefunds = $queuedRefundRepository->getQueuedRefundsByStatus(QueuedRefund::STATUS_PENDING);
        self::assertCount(1, $queuedRefunds);
        /** @var QueuedRefund $queuedRefund */
        $queuedRefund = $queuedRefunds[0];
        self::assertSame(OrderFixture::TEST_PAYMENT_ID, $queuedRefund->getPaymentId());
        self::assertSame(
            $creditMemo->getEntityId(),
            $queuedRefund->getMetaData()[RefundProcessor::KEY_CREDITMEMO_ID]
        );

        // Fake cron (to check if refund is updated)
        $this->reset();
        // Trigger cron:
        $this->apiResponseFixture->setupApiResponse([
            'getPayment:payment.paid',
            'ingenicoRefund:refund.refund_requested',
        ], $order);
        /** @var RefundQueue $refundQueueCron */
        $refundQueueCron = $this->objectManager->get(RefundQueue::class);
        $refundQueueCron->execute();

        // Do assertions:
        $this->_testCreateCreditMemoFlow($order);
        $queuedRefunds = $queuedRefundRepository->getQueuedRefundsByStatus(QueuedRefund::STATUS_PROCESSED);
        self::assertCount(1, $queuedRefunds);
        /** @var QueuedRefund $queuedRefund */
        $queuedRefund = $queuedRefunds[0];
        self::assertSame(
            $creditMemo->getEntityId(),
            $queuedRefund->getMetaData()[RefundProcessor::KEY_CREDITMEMO_ID]
        );
    }

    private function createRefundQueueOrderStatuses()
    {
        $this->createOrderStatus('processing_refund_queued', Order::STATE_PROCESSING);
        $this->createOrderStatus('complete_refund_queued', Order::STATE_COMPLETE);
        $this->createOrderStatus('closed_refund_queued', Order::STATE_CLOSED);
    }

    private function createOrderStatus(string $statusCode, string $orderState)
    {
        /** @var Status $status */
        $status = $this->objectManager->create(Status::class);
        /** @var StatusResource $statusResource */
        $statusResource = $this->objectManager->get(StatusResource::class);
        $status->setData([
            'status' => $statusCode,
            'label' => $statusCode,
        ]);
        $statusResource->save($status);
        $status->assignState($orderState, false, false);
    }
}
