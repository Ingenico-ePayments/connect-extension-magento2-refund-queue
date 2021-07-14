<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model;

use Ingenico\Connect\Model\Config as BaseConfig;

class Config extends BaseConfig
{
    private const CONFIG_QUEUED_REFUND_PROCESSING_STATUS = 'ingenico_epayments/settings/queued_refund_status_processing';
    private const CONFIG_QUEUED_REFUND_COMPLETE_STATUS = 'ingenico_epayments/settings/queued_refund_status_complete';
    private const CONFIG_QUEUED_REFUND_CLOSED_STATUS = 'ingenico_epayments/settings/queued_refund_status_closed';
    private const CONFIG_REFUND_QUEUE_CRON_SCHEDULE = 'ingenico_epayments/settings/refund_queue_cron_schedule';

    public function getQueuedRefundProcessingStatus(): string
    {
        return (string) $this->getValue(self::CONFIG_QUEUED_REFUND_PROCESSING_STATUS);
    }

    public function getQueuedRefundCompleteStatus(): string
    {
        return (string) $this->getValue(self::CONFIG_QUEUED_REFUND_COMPLETE_STATUS);
    }

    public function getQueuedRefundClosedStatus(): string
    {
        return (string) $this->getValue(self::CONFIG_QUEUED_REFUND_CLOSED_STATUS);
    }
}
