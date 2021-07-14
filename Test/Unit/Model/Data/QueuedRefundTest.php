<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Test\Unit\Model\Data;

use DateTime;
use Ingenico\Connect\Sdk\Domain\Refund\RefundRequest;
use Ingenico\RefundQueue\Model\Data\QueuedRefund;
use PHPUnit\Framework\TestCase;

class QueuedRefundTest extends TestCase
{
    public const FAKE_PAYMENT_ID = '12345';
    public const FAKE_MERCHANT_ID = '67890';

    /**
     * @var QueuedRefund
     */
    private $subject;

    public function testDefaultDataModel(): void
    {
        // Verify:
        self::assertSame(
            '2020-10-15 12:30:00',
            $this->subject->getCreationTime()->format('Y-m-d H:i:s'),
        );
        self::assertSame(QueuedRefund::STATUS_PENDING, $this->subject->getStatus());
        self::assertNull($this->subject->getId());
        self::assertNull($this->subject->getUpdateTime());
    }

    public function testStatusChangesToProcessed(): void
    {
        // Exercise:
        $this->subject->process();

        // Verify:
        self::assertSame(QueuedRefund::STATUS_PROCESSED, $this->subject->getStatus());
        self::assertNotNull($this->subject->getUpdateTime());
    }

    public function testStatusChangesToFailed(): void
    {
        // Exercise:
        $this->subject->fail();

        // Verify:
        self::assertSame(QueuedRefund::STATUS_FAILED, $this->subject->getStatus());
        self::assertNotNull($this->subject->getUpdateTime());
    }

    public static function createFakeQueuedRefund(?int $id = null): QueuedRefund
    {
        return new QueuedRefund(
            self::FAKE_PAYMENT_ID,
            self::FAKE_MERCHANT_ID,
            new RefundRequest(),
            new DateTime('2020-10-15 12:30:00'),
            QueuedRefund::STATUS_PENDING,
            $id,
        );
    }

    protected function setUp(): void
    {
        $this->subject = self::createFakeQueuedRefund();
    }
}
