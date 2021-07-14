<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Api;

use Exception;
use Ingenico\Connect\Sdk\Domain\Refund\RefundRequest;

interface RefundProcessorInterface
{
    /**
     * This method gets called if a payment is eligible for refunding.
     *
     * @param string $merchantId
     * @param string $paymentId
     * @param RefundRequest $refundRequest
     * @param array|null $metaData
     * @throws Exception if processing the refund request fails
     */
    public function process(
        string $merchantId,
        string $paymentId,
        RefundRequest $refundRequest,
        ?array $metaData
    ): void;

    /**
     * If retry is allowed, the Queued Refund will be left in a PENDING
     * state if the process() throws an exception.
     * If retry is not allowed, the Queued Refund will be set in a FAILED
     * state and will not be processed in the next run.
     *
     * @return bool
     */
    public function isRetryAllowed(): bool;
}
