<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class RefundQueueSchedule implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => '0 0 * * *', 'label' => __('Once, at midnight')],
            ['value' => '0 0,3,6 * * *', 'label' => __('At midnight, 3:00 AM, and 6:00 AM')],
            ['value' => '0 */3 * * *', 'label' => __('Every 3 hours')],
            ['value' => '0 * * * *', 'label' => __('Every hour')],
        ];
    }
}
