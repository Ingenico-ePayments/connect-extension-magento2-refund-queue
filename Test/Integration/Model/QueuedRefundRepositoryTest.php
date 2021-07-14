<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Test\Integration\Model;

use DateTime;
use Ingenico\Connect\Sdk\Domain\Refund\RefundRequest;
use Ingenico\Connect\Test\Integration\AbstractTestCase;
use Ingenico\RefundQueue\Model\Data\QueuedRefund;
use Ingenico\RefundQueue\Model\Data\QueuedRefundFactory;
use Ingenico\RefundQueue\Model\QueuedRefundRepository;

class QueuedRefundRepositoryTest extends AbstractTestCase
{
    /**
     * @var QueuedRefundRepository
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setup();
        $this->subject = $this->objectManager->get(QueuedRefundRepository::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testSave(): void
    {
        // Setup & Exercise:
        $queuedRefund = $this->createQueuedRefund();

        // Verify:
        self::assertSame('12345', $queuedRefund->getPaymentId());
        self::assertSame('67890', $queuedRefund->getMerchantId());
        self::assertInstanceOf(RefundRequest::class, $queuedRefund->getRefundRequest());
        self::assertInstanceOf(DateTime::class, $queuedRefund->getCreationTime());
        self::assertSame(QueuedRefund::STATUS_PENDING, $queuedRefund->getStatus());
        self::assertNotNull($queuedRefund->getId());
        self::assertNull($queuedRefund->getUpdateTime());
        self::assertNull($queuedRefund->getMetaData());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testSaveMetaData(): void
    {
        // Setup & Exercise:
        $metaData = ['foo1' => 'bar1', 'foo2' => 'bar2'];
        $queuedRefund = $this->createQueuedRefund(
            QueuedRefund::STATUS_PENDING,
            $metaData
        );

        // Verify:
        self::assertEquals($metaData, $queuedRefund->getMetaData());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testUpdate(): void
    {
        // Setup:
        $queuedRefund = $this->createQueuedRefund();

        // Exercise:
        $id = $queuedRefund->getId();
        $creationTime = $queuedRefund->getCreationTime();
        $queuedRefund->fail();
        $queuedRefund = $this->subject->save($queuedRefund);

        // Verify:
        self::assertSame($id, $queuedRefund->getId());
        self::assertEquals($creationTime, $queuedRefund->getCreationTime());
        self::assertSame(QueuedRefund::STATUS_FAILED, $queuedRefund->getStatus());
        self::assertInstanceOf(DateTime::class, $queuedRefund->getUpdateTime());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testLoad(): void
    {
        // Setup:
        $queuedRefund = $this->createQueuedRefund();
        $id = $queuedRefund->getId();

        // Add timeout to test update times:
        sleep(1);

        // Exercise:
        $loadedQueuedRefund = $this->subject->load($id);

        // Verify:
        self::assertSame($queuedRefund->getId(), $loadedQueuedRefund->getId());
        self::assertSame($queuedRefund->getPaymentId(), $loadedQueuedRefund->getPaymentId());
        self::assertSame($queuedRefund->getMerchantId(), $loadedQueuedRefund->getMerchantId());
        self::assertSame($queuedRefund->getStatus(), $loadedQueuedRefund->getStatus());
        self::assertEquals($queuedRefund->getCreationTime(), $loadedQueuedRefund->getCreationTime());
        self::assertEquals($queuedRefund->getUpdateTime(), $loadedQueuedRefund->getUpdateTime());
        self::assertEquals($queuedRefund->getRefundRequest(), $loadedQueuedRefund->getRefundRequest());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testGetQueuedRefundsByStatus(): void
    {
        // Setup:
        $testSubjects = [
            QueuedRefund::STATUS_PENDING => 3,
            QueuedRefund::STATUS_FAILED => 2,
            QueuedRefund::STATUS_PROCESSED => 5,
        ];
        foreach ($testSubjects as $status => $amount) {
            for ($i = 0; $i < $amount; $i += 1) {
                $this->createQueuedRefund($status);
            }
        }

        // Exercise:
        $pendingCollection = $this->subject->getQueuedRefundsByStatus(QueuedRefund::STATUS_PENDING);
        $failedCollection = $this->subject->getQueuedRefundsByStatus(QueuedRefund::STATUS_FAILED);
        $processedCollection = $this->subject->getQueuedRefundsByStatus(QueuedRefund::STATUS_PROCESSED);

        // Verify:
        self::assertCount(3, $pendingCollection);
        self::assertCount(2, $failedCollection);
        self::assertCount(5, $processedCollection);
        foreach ($pendingCollection as $queudRefund) {
            self::assertSame(QueuedRefund::STATUS_PENDING, $queudRefund->getStatus());
        }
        foreach ($failedCollection as $queudRefund) {
            self::assertSame(QueuedRefund::STATUS_FAILED, $queudRefund->getStatus());
        }
        foreach ($processedCollection as $queudRefund) {
            self::assertSame(QueuedRefund::STATUS_PROCESSED, $queudRefund->getStatus());
        }
    }

    /**
     * @param string $status
     * @param array|null $metaData
     * @return QueuedRefund
     */
    private function createQueuedRefund(
        string $status = QueuedRefund::STATUS_PENDING,
        ?array $metaData = null
    ): QueuedRefund {
        $modelFactory = $this->objectManager->get(QueuedRefundFactory::class);
        $model = $modelFactory->create([
            'paymentId' => '12345',
            'merchantId' => '67890',
            'refundRequest' => new RefundRequest(),
            'creationTime' => new DateTime(),
            'status' => $status,
            'metaData' => $metaData,
        ]);

        // Exercise:
        return $this->subject->save($model);
    }
}
