<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Test\Unit\Model\Data\QueuedRefund;

use DateTime;
use Ingenico\RefundQueue\Model\Data\QueuedRefund\Collection;
use Ingenico\RefundQueue\Test\Unit\Model\Data\QueuedRefundTest;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    private $subject;

    public function testAddingOfQueuedRefunds(): void
    {
        // Exercise:
        for ($i = 1; $i <= 3; $i += 1) {
            $queuedRefund = QueuedRefundTest::createFakeQueuedRefund($i);
            $this->subject->append($queuedRefund);
        }

        // Verify:
        self::assertCount(3, $this->subject);
        self::assertSame(2, $this->subject->offsetGet(1)->getId());
    }

    /**
     * @param int|string|object $value
     * @dataProvider invalidValueDataProvider
     */
    public function testOnlyQueuedRefundsCanBePushed($value): void
    {
        // Set expectations:
        $this->expectException(InvalidArgumentException::class);
        // Exercise:
        $this->subject->append($value);
    }

    public function invalidValueDataProvider(): array
    {
        return [
            ['x'],
            [1234],
            [new DateTime('NOW')],
        ];
    }

    protected function setUp(): void
    {
        $this->subject = new Collection();
    }
}
