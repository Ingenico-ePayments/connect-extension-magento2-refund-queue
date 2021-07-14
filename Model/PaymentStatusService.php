<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model;

use Ingenico\Connect\Sdk\Client;

class PaymentStatusService
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function isRefundable(string $paymentId, string $merchantId): bool
    {
        $paymentResponse = $this->client
            ->merchant($merchantId)
            ->payments()
            ->get($paymentId);

        return $paymentResponse->statusOutput->isRefundable;
    }
}
