<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model;

use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\CallContextBuilder;
use Ingenico\Connect\Model\Ingenico\Status\Refund\ResolverInterface;
use Ingenico\Connect\Sdk\Domain\Refund\RefundRequest;
use Ingenico\RefundQueue\Api\RefundProcessorInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;

class RefundProcessor implements RefundProcessorInterface
{
    public const KEY_CREDITMEMO_ID = 'creditmemo_id';

    /**
     * @var CallContextBuilder
     */
    private $callContextBuilder;

    /**
     * @var ClientInterface
     */
    private $ingenicoClient;

    /**
     * @var CreditmemoRepositoryInterface
     */
    private $creditmemoRepository;

    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    public function __construct(
        CallContextBuilder $callContextBuilder,
        ClientInterface $ingenicoClient,
        CreditmemoRepositoryInterface $creditMemoRepository,
        ResolverInterface $statusResolver
    ) {
        $this->callContextBuilder = $callContextBuilder;
        $this->ingenicoClient = $ingenicoClient;
        $this->creditmemoRepository = $creditMemoRepository;
        $this->statusResolver = $statusResolver;
    }

    public function process(
        string $merchantId,
        string $paymentId,
        RefundRequest $refundRequest,
        ?array $metaData
    ): void {
        $this->validate($metaData);
        $creditMemo = $this->creditmemoRepository->get($metaData[self::KEY_CREDITMEMO_ID]);
        $callContext = $this->callContextBuilder->create();
        $response = $this->ingenicoClient->ingenicoRefund(
            $paymentId,
            $refundRequest,
            $callContext,
            $creditMemo->getStoreId()
        );

        // Call status resolver:
        $this->statusResolver->resolve(
            $creditMemo,
            $response
        );

        // Save creditMemo:
        $this->creditmemoRepository->save($creditMemo);
    }

    public function isRetryAllowed(): bool
    {
        return true;
    }

    private function validate(?array $metaData)
    {
        $keys = [self::KEY_CREDITMEMO_ID];

        foreach ($keys as $key) {
            if (!array_key_exists($key, $metaData)) {
                throw new \InvalidArgumentException(
                    'Missing meta data: ' . $key
                );
            }
        }
    }
}
