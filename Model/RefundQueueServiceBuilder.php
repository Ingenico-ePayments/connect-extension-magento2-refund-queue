<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model;

use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\RefundQueue\Model\RefundQueueService;
use Ingenico\RefundQueue\Model\PaymentStatusServiceFactory;
use Ingenico\RefundQueue\Model\RefundQueueServiceFactory;

class RefundQueueServiceBuilder
{
    /**
     * @var ClientInterface
     */
    private $ingenicoClient;

    /**
     * @var RefundQueueServiceFactory
     */
    private $refundQueueServiceFactory;

    /**
     * @var PaymentStatusServiceFactory
     */
    private $paymentStatusServiceFactory;

    public function __construct(
        ClientInterface $ingenicoClient,
        RefundQueueServiceFactory $refundQueueServiceFactory,
        PaymentStatusServiceFactory $paymentStatusServiceFactory
    ) {
        $this->ingenicoClient = $ingenicoClient;
        $this->refundQueueServiceFactory = $refundQueueServiceFactory;
        $this->paymentStatusServiceFactory = $paymentStatusServiceFactory;
    }

    public function build(int $storeId): RefundQueueService
    {
        return $this->refundQueueServiceFactory->create(
            [
                'paymentStatusService' => $this->paymentStatusServiceFactory->create([
                    'client' => $this->ingenicoClient->getIngenicoClient($storeId),
                ]),
            ]
        );
    }
}
