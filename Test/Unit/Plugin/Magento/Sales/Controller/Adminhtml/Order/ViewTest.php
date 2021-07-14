<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Test\Unit\Plugin\Magento\Sales\Controller\Adminhtml\Order;

use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\RefundQueue\Api\RefundQueueManagementInterface;
use Ingenico\RefundQueue\Plugin\Magento\Sales\Controller\Adminhtml\Order\View;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\View as ViewController;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditMemoCollection;
use PHPUnit\Framework\MockObject\MockObject;

class ViewTest extends AbstractTestCase
{
    /**
     * @var View
     */
    private $subject;

    /**
     * @var ViewController|MockObject
     */
    private $mockedViewController;

    /**
     * @var Order|MockObject
     */
    private $mockedOrder;

    /**
     * @var RefundQueueManagementInterface|MockObject
     */
    private $mockedRefundQueueManagement;

    protected function setUp(): void
    {
        $this->mockedViewController = $this->getMockBuilder(ViewController::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var RequestInterface|MockObject $mockedRequest */
        $mockedRequest = $this->getMockBuilder(RequestInterface::class)->getMock();
        $mockedRequest->method('getParam')->with('order_id')->willReturn(1);
        $this->mockedOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var OrderPaymentInterface|MockObject $mockedPayment */
        $mockedPayment = $this->getMockBuilder(OrderPaymentInterface::class)->getMock();
        $this->mockedOrder->method('getPayment')->willReturn($mockedPayment);
        /** @var CreditMemoCollection|MockObject $mockedCreditMemosCollection */
        $mockedCreditMemosCollection = $this->getMockBuilder(CreditMemoCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockedCreditMemosCollection->method('addFieldToFilter')->willReturn([
            $this->getMockBuilder(CreditmemoInterface::class)->getMock(),
        ]);
        $this->mockedOrder->method('getCreditmemosCollection')->willReturn($mockedCreditMemosCollection);
        /** @var OrderRepositoryInterface|MockObject $mockedOrderRepository */
        $mockedOrderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)->getMock();
        $mockedOrderRepository->method('get')->willReturn($this->mockedOrder);
        $this->mockedViewController->method('getRequest')->willReturn($mockedRequest);
        $this->mockedRefundQueueManagement = $this->getMockBuilder(RefundQueueManagementInterface::class)->getMock();
        $this->subject = $this->getObjectManager()->getObject(
            View::class,
            [
                'orderRepository' => $mockedOrderRepository,
                'refundQueueManagement' => $this->mockedRefundQueueManagement,
            ]
        );
    }

    public function testNonIngenicoOrderWillBeIgnored()
    {
        // Setup:
        $this->mockedOrder->getPayment()->method('getMethod')->willReturn('foo');

        // Set expectations:
        $this->mockedOrder
            ->expects(self::never())
            ->method('getCreditmemosCollection');

        // Exercise:
        $this->subject->beforeExecute($this->mockedViewController);
    }

    public function testRefundQueueWillNotBeProcessedIfNothingIsQueued(): void
    {
        // Setup:
        $this->mockedOrder->getPayment()->method('getMethod')->willReturn(ConfigProvider::CODE);
        $this->mockedRefundQueueManagement
            ->method('isQueued')
            ->willReturn(false);
        $this->mockedRefundQueueManagement
            ->expects(self::never())
            ->method('processQueuedRefund');

        // Exercise:
        $result = $this->subject->beforeExecute($this->mockedViewController);

        // Verify:
        self::assertNull($result);
    }

    public function testRefundQueueWillBeProcessedIfSomethingIsQueued(): void
    {
        // Setup:
        $this->mockedOrder->getPayment()->method('getMethod')->willReturn(ConfigProvider::CODE);
        $this->mockedRefundQueueManagement
            ->method('isQueued')
            ->willReturn(true);
        $this->mockedRefundQueueManagement
            ->expects(self::once())
            ->method('processQueuedRefund');

        // Exercise:
        $result = $this->subject->beforeExecute($this->mockedViewController);

        // Verify:
        self::assertNull($result);
    }
}
