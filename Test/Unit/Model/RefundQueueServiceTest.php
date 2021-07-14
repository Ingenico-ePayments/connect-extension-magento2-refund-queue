<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Test\Unit\Model;

use Exception;
use Ingenico\RefundQueue\Api\QueuedRefundRepositoryInterface;
use Ingenico\RefundQueue\Api\RefundProcessorInterface;
use Ingenico\RefundQueue\Model\Data\QueuedRefund;
use Ingenico\RefundQueue\Model\Data\QueuedRefund\Collection;
use Ingenico\RefundQueue\Model\PaymentStatusService;
use Ingenico\RefundQueue\Model\RefundQueueService;
use Ingenico\RefundQueue\Test\Unit\Model\Data\QueuedRefundTest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RefundQueueServiceTest extends TestCase
{
    /**
     * @var RefundQueueService
     */
    private $subject;

    /**
     * @var MockObject|QueuedRefundRepositoryInterface
     */
    private $mockedRepository;

    /**
     * @var MockObject|RefundProcessorInterface
     */
    private $mockedRefundProcessor;

    /**
     * @var MockObject|PaymentStatusService
     */
    private $mockedPaymentStatusService;

    /**
     * @var QueuedRefund
     */
    private $fakeQueuedRefund;

    public function testNonRefundablePayment(): void
    {
        // Setup:
        $this->setupFakeCollection();
        $this->setupPaymentStatusService(false);
        $this->mockedRefundProcessor
            ->expects(self::never())
            ->method('process');
        $this->mockedRepository
            ->expects(self::never())
            ->method('save');

        // Exercise:
        $this->subject->processRefundQueue();

        // Verify:
        self::assertNull($this->fakeQueuedRefund->getUpdateTime());
        self::assertSame(
            QueuedRefund::STATUS_PENDING,
            $this->fakeQueuedRefund->getStatus(),
        );
    }

    public function testRefundablePayment(): void
    {
        // Setup:
        $this->setupFakeCollection();
        $this->setupPaymentStatusService(true);
        $this->mockedRefundProcessor
            ->expects(self::once())
            ->method('process');
        $this->mockedRepository
            ->expects(self::once())
            ->method('save');

        // Exercise:
        $this->subject->processRefundQueue();

        // Verify:
        self::assertNotNull($this->fakeQueuedRefund->getUpdateTime());
        self::assertSame(
            QueuedRefund::STATUS_PROCESSED,
            $this->fakeQueuedRefund->getStatus(),
        );
    }

    public function testRefundablePaymentFailed(): void
    {
        // Setup:
        $this->setupFakeCollection();
        $this->setupPaymentStatusService(true);
        $this->mockedRefundProcessor
            ->expects(self::once())
            ->method('process')
            ->willThrowException(new Exception('Whoops!'));
        $this->mockedRepository
            ->expects(self::once())
            ->method('save');

        // Exercise:
        $this->subject->processRefundQueue();

        // Verify:
        self::assertNotNull($this->fakeQueuedRefund->getUpdateTime());
        self::assertSame(
            QueuedRefund::STATUS_FAILED,
            $this->fakeQueuedRefund->getStatus(),
        );
    }

    public function testRefundablePaymentFailedButRetryable(): void
    {
        // Setup:
        $this->setupFakeCollection();
        $this->setupPaymentStatusService(true);
        $this->mockedRefundProcessor
            ->expects(self::once())
            ->method('process')
            ->willThrowException(new Exception('Whoops!'));
        $this->mockedRefundProcessor
            ->method('isRetryAllowed')
            ->willReturn(true);
        $this->mockedRepository
            ->expects(self::once())
            ->method('save');

        // Exercise:
        $this->subject->processRefundQueue();

        // Verify:
        self::assertNotNull($this->fakeQueuedRefund->getUpdateTime());
        self::assertSame(
            QueuedRefund::STATUS_PENDING,
            $this->fakeQueuedRefund->getStatus(),
        );
    }

    protected function setUp(): void
    {
        $this->mockedRepository = $this->getMockBuilder(QueuedRefundRepositoryInterface::class)->getMock();
        $this->mockedRefundProcessor = $this->getMockBuilder(RefundProcessorInterface::class)->getMock();
        $this->mockedPaymentStatusService = $this
            ->getMockBuilder(PaymentStatusService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = new RefundQueueService(
            $this->mockedRepository,
            $this->mockedRefundProcessor,
            $this->mockedPaymentStatusService,
        );
    }

    private function setupFakeCollection(): void
    {
        $this->fakeQueuedRefund = QueuedRefundTest::createFakeQueuedRefund();
        $fakeCollection = new Collection([$this->fakeQueuedRefund]);
        $this->mockedRepository
            ->method('getQueuedRefundsByStatus')
            ->willReturn($fakeCollection);
    }

    private function setupPaymentStatusService(bool $isRefundable): void
    {
        $this->mockedPaymentStatusService
            ->expects(self::once())
            ->method('isRefundable')
            ->with(QueuedRefundTest::FAKE_PAYMENT_ID)
            ->willReturn($isRefundable);
    }
}
