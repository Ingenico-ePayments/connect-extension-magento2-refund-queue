<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Test\Unit\Plugin\Magento\Sales\Model\Service;

use Closure;
use Ingenico\RefundQueue\Api\QueuedRefundRepositoryInterface;
use Ingenico\RefundQueue\Api\RefundQueueManagementInterface;
use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\RefundQueue\Model\Data\QueuedRefund;
use Ingenico\RefundQueue\Plugin\Magento\Sales\Model\Service\CreditmemoService;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Service\CreditmemoService as BaseCreditmemoService;
use PHPUnit\Framework\MockObject\MockObject;

class CreditMemoServiceTest extends AbstractTestCase
{
    /**
     * @var CreditmemoService
     */
    private $subject;

    /**
     * @var MockObject|OrderPaymentInterface
     */
    private $mockedPayment;

    /**
     * @var MockObject|RefundQueueManagementInterface
     */
    private $mockedRefundQueueManagement;

    /**
     * @var MockObject|CreditmemoRepositoryInterface
     */
    private $mockedCreditMemoRepository;

    /**
     * @var MockObject|QueuedRefundRepositoryInterface
     */
    private $mockedQueuedRefundRepository;

    protected function setUp(): void
    {
        $this->mockedPayment = $this->getMockBuilder(OrderPaymentInterface::class)->getMock();
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $mockedOrder->method('getPayment')->willReturn($this->mockedPayment);
        /** @var OrderRepositoryInterface|MockObject $mockedOrderRepository */
        $mockedOrderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)->getMock();
        $mockedOrderRepository->method('get')->willReturn($mockedOrder);
        $this->mockedRefundQueueManagement = $this->getMockBuilder(RefundQueueManagementInterface::class)->getMock();
        $this->mockedCreditMemoRepository = $this->getMockBuilder(CreditmemoRepositoryInterface::class)->getMock();
        $this->mockedQueuedRefundRepository = $this->getMockBuilder(QueuedRefundRepositoryInterface::class)->getMock();
        $this->subject = $this->getObjectManager()->getObject(
            CreditmemoService::class,
            [
                'orderRepository' => $mockedOrderRepository,
                'creditMemoRepository' => $this->mockedCreditMemoRepository,
                'queuedRefundRepository' => $this->mockedQueuedRefundRepository,
                'refundQueueManagement' => $this->mockedRefundQueueManagement,
            ]
        );
    }

    public function testOfflineRefundIsIgnored(): void
    {
        self::markTestSkipped('postponed');

        // Setup:
        $mockedCreditMemoService = $this->mockCreditMemoService();
        $mockedCallable = $this->mockCallable();
        $this->mockedPayment
            ->method('getMethod')
            ->willReturn(ConfigProvider::CODE);

        // Exercise:
        $result = $this->subject->aroundCancel(
            $mockedCreditMemoService,
            $mockedCallable,
            1
        );

        // Verify:
        self::assertSame('called', $result);
    }

    public function testRefundQueueIsNotCancelledIfNotInQueue(): void
    {
        self::markTestSkipped('postponed');

        // Setup:
        $mockedCreditMemoService = $this->mockCreditMemoService();
        $mockedCallable = $this->mockCallable();
        $mockedCreditMemo = $this->mockCreditMemo();
        $this->mockedCreditMemoRepository
            ->method('get')
            ->willReturn($mockedCreditMemo);
        $this->mockedPayment
            ->method('getMethod')
            ->willReturn(ConfigProvider::CODE);
        $this->mockedRefundQueueManagement
            ->method('isQueued')
            ->willReturn(false);

        // Exercise:
        $result = $this->subject->aroundCancel(
            $mockedCreditMemoService,
            $mockedCallable,
            1
        );

        // Verify:
        self::assertSame('called', $result);
    }

    public function testRefundQueueIsCancelledIfInQueue(): void
    {
        self::markTestSkipped('postponed');

        // Setup:
        $mockedCreditMemoService = $this->mockCreditMemoService();
        $mockedCallable = $this->mockCallable();
        $mockedCreditMemo = $this->mockCreditMemo();
        $this->mockedCreditMemoRepository
            ->method('get')
            ->willReturn($mockedCreditMemo);
        $this->mockedPayment
            ->method('getMethod')
            ->willReturn(ConfigProvider::CODE);
        $this->mockedRefundQueueManagement
            ->method('isQueued')
            ->willReturn(true);
        $mockedQueuedRefund = $this->getMockBuilder(QueuedRefund::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockedRefundQueueManagement
            ->method('getQueuedRefundForCreditMemo')
            ->willReturn($mockedQueuedRefund);

        // Set expectations:
        $mockedCreditMemo->expects(self::once())
            ->method('setState')
            ->with(Creditmemo::STATE_CANCELED);
        $this->mockedCreditMemoRepository->expects(self::once())
            ->method('save')
            ->with($mockedCreditMemo);
        $mockedQueuedRefund->expects(self::once())
            ->method('cancel');
        $this->mockedQueuedRefundRepository->expects(self::once())
            ->method('save')
            ->with($mockedQueuedRefund);

        // Exercise:
        $result = $this->subject->aroundCancel(
            $mockedCreditMemoService,
            $mockedCallable,
            1
        );

        // Verify:
        self::assertSame(null, $result);
    }

    /**
     * @return MockObject|BaseCreditmemoService
     */
    private function mockCreditMemoService(): MockObject
    {
        return $this->getMockBuilder(BaseCreditmemoService::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function mockCallable(): Closure
    {
        return function () {
            return 'called';
        };
    }

    /**
     * @return MockObject|CreditmemoInterface
     */
    private function mockCreditMemo(): MockObject
    {
        return $this->getMockBuilder(CreditmemoInterface::class)->getMock();
    }
}
