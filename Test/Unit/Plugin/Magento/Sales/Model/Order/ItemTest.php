<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Test\Unit\Plugin\Magento\Sales\Model\Order;

use ArrayIterator;
use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Ingenico\RefundQueue\Api\RefundQueueManagementInterface;
use Ingenico\RefundQueue\Plugin\Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditMemoItem;
use Magento\Sales\Model\Order\Item as BaseOrderItem;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use PHPUnit\Framework\MockObject\MockObject;

class ItemTest extends AbstractTestCase
{
    /**
     * @var Item
     */
    private $subject;

    /**
     * @var MockObject|RefundQueueManagementInterface
     */
    private $mockedRefundQueueManagement;

    /**
     * @var MockObject
     */
    private $mockedCreditMemoFactory;

    protected function setUp(): void
    {
        $this->mockedRefundQueueManagement = $this
            ->getMockBuilder(RefundQueueManagementInterface::class)
            ->getMock();
        $this->mockedCreditMemoFactory = $this->getMockForFactory(CreditmemoCollection::class, true);
        self::$returnedMockedObjects[CreditmemoCollection::class]
            ->method('addFieldToFilter')
            ->willReturnSelf();
        self::$returnedMockedObjects[CreditmemoCollection::class]
            ->method('setOrderFilter')
            ->willReturnSelf();
        $this->subject = $this->getObjectManager()->getObject(
            Item::class,
            [
                'refundQueueManagement' => $this->mockedRefundQueueManagement,
                'creditMemoCollectionFactory' => $this->mockedCreditMemoFactory,
            ]
        );
    }

    public function testNonIngenicoCreditmemosAreIgnored(): void
    {
        // Setup:
        $subject = $this->mockOrderItem();
        $this->mockPaymentMethod($subject, 'foo');

        // Set expectations:
        $this->mockedCreditMemoFactory
            ->expects(self::never())
            ->method('create');

        // Exercise:
        $result = $this->subject->afterGetQtyRefunded($subject, 0.0);

        // Verify:
        self::assertSame(0.0, $result);
    }

    public function testIngenicoCreditmemosAreNotIgnored(): void
    {
        // Setup:
        $subject = $this->mockOrderItem();
        $this->mockPaymentMethod($subject, ConfigProvider::CODE);
        $this->mockCreditmemosCollectionData([
            $this->mockCreditMemo([
                $this->mockCreditMemoItem(42, 1.00),
            ]),
        ]);
        $this->mockedRefundQueueManagement
            ->method('isQueued')
            ->willReturn(true);
        $subject->method('getId')->willReturn(42);

        // Set expectations:
        $this->mockedCreditMemoFactory
            ->expects(self::once())
            ->method('create');

        // Exercise:
        $result = $this->subject->afterGetQtyRefunded($subject, 0.0);

        // Verify:
        self::assertSame(1.0, $result);
    }

    /**
     * @return MockObject|BaseOrderItem
     */
    private function mockOrderItem()
    {
        /** @var MockObject|BaseOrderItem $mockedOrderItem */
        $mockedOrderItem = $this->getMockBuilder(BaseOrderItem::class)
            ->disableOriginalConstructor()
            ->getMock();

        return $mockedOrderItem;
    }

    /**
     * @param BaseOrderItem|MockObject $subject
     * @param string $paymentMethod
     */
    private function mockPaymentMethod(BaseOrderItem $subject, string $paymentMethod)
    {
        $mockedOrder = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var Payment|MockObject $mockedPayment */
        $mockedPayment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockedPayment->method('getMethod')->willReturn($paymentMethod);
        $mockedOrder->method('getPayment')->willReturn($mockedPayment);
        $mockedOrder->method('getId')->willReturn(1);
        $subject->method('getOrder')->willReturn($mockedOrder);
    }

    /**
     * @param array $data
     */
    private function mockCreditmemosCollectionData(array $data = []): void
    {
        $iterator = new ArrayIterator($data);
        self::$returnedMockedObjects[CreditmemoCollection::class]
            ->method('getIterator')->willReturn($iterator);
    }

    /**
     * @return MockObject|Creditmemo
     */
    private function mockCreditMemo(array $items = [])
    {
        /** @var MockObject|Creditmemo $mockedCreditMemo */
        $mockedCreditMemo = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockedCreditMemo->method('getItems')->willReturn($items);

        return $mockedCreditMemo;
    }

    /**
     * @param int $orderItemId
     * @param float $qty
     * @return CreditMemoItem|MockObject
     */
    private function mockCreditMemoItem(int $orderItemId, float $qty)
    {
        /** @var CreditMemoItem|MockObject $mockedCreditMemoItem */
        $mockedCreditMemoItem = $this->getMockBuilder(CreditMemoItem::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockedCreditMemoItem->method('getOrderItemId')->willReturn($orderItemId);
        $mockedCreditMemoItem->method('getQty')->willReturn($qty);

        return $mockedCreditMemoItem;
    }
}
