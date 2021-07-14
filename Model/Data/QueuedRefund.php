<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model\Data;

use DateTime;
use Ingenico\Connect\Sdk\Domain\Refund\RefundRequest;

// phpcs:disable ObjectCalisthenics.Metrics.MethodPerClassLimit.ObjectCalisthenics\Sniffs\Metrics\MethodPerClassLimitSniff

class QueuedRefund
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var string
     */
    private $paymentId;

    /**
     * @var string
     */
    private $merchantId;

    /**
     * @var RefundRequest
     */
    private $refundRequest;

    /**
     * @var DateTime
     */
    private $creationTime;

    /**
     * @var string
     */
    private $status;

    /**
     * @var int|null
     */
    private $id;

    /**
     * @var DateTime|null
     */
    private $updateTime;

    /**
     * @var array|null
     */
    private $metaData;

    public function __construct(
        string $paymentId,
        string $merchantId,
        RefundRequest $refundRequest,
        DateTime $creationTime,
        string $status = self::STATUS_PENDING,
        ?int $id = null,
        ?DateTime $updateTime = null,
        ?array $metaData = null
    ) {
        $this->paymentId = $paymentId;
        $this->merchantId = $merchantId;
        $this->refundRequest = $refundRequest;
        $this->creationTime = $creationTime;
        $this->status = $status;
        $this->id = $id;
        $this->updateTime = $updateTime;
        $this->metaData = $metaData;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function getRefundRequest(): RefundRequest
    {
        return $this->refundRequest;
    }

    public function getCreationTime(): DateTime
    {
        return $this->creationTime;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUpdateTime(): ?DateTime
    {
        return $this->updateTime;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function getMetaData(): ?array
    {
        return $this->metaData;
    }

    public function process(): void
    {
        $this->status = self::STATUS_PROCESSED;
        $this->updateTime();
    }

    public function fail(): void
    {
        $this->status = self::STATUS_FAILED;
        $this->updateTime();
    }

    public function cancel(): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->updateTime();
    }

    public function updateTime()
    {
        $this->updateTime = new DateTime('NOW');
    }
}
