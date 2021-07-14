<?php

declare(strict_types=1);

namespace Ingenico\RefundQueue\Setup\Patch\Schema;

use Ingenico\RefundQueue\Model\QueuedRefund;
use Ingenico\RefundQueue\Model\ResourceModel\QueuedRefund as QueuedRefundResource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Zend_Db_Exception;

class RefundQueuePatch implements SchemaPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $setup;

    public function __construct(
        SchemaSetupInterface $setup
    ) {
        $this->setup = $setup;
    }

    /**
     * @return RefundQueuePatch
     * @throws Zend_Db_Exception
     */
    public function apply()
    {
        $connection = $this->setup->getConnection();
        $table = $connection->newTable(QueuedRefundResource::TABLE_NAME);
        $table->addColumn(
            QueuedRefund::KEY_ID,
            Table::TYPE_INTEGER,
            null,
            [
                'primary' => true,
                'identity' => true,
                'nullable' => false,
                'unsigned' => true,
            ],
            'ID'
        );
        $table->addColumn(
            QueuedRefund::KEY_PAYMENT_ID,
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false,
            ],
            'Payment ID'
        );
        $table->addColumn(
            QueuedRefund::KEY_MERCHANT_ID,
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false,
            ],
            'Merchant ID'
        );
        $table->addColumn(
            QueuedRefund::KEY_STATUS,
            Table::TYPE_TEXT,
            255,
            [
                'nullable' => false,
            ],
            'Status'
        );
        $table->addColumn(
            QueuedRefund::KEY_REFUND_REQUEST,
            Table::TYPE_TEXT,
            null,
            [
                'nullable' => false,
            ],
            'Refund Request'
        );
        $table->addColumn(
            QueuedRefund::KEY_META_DATA,
            Table::TYPE_TEXT,
            null,
            [
                'nullable' => true,
            ],
            'Meta Data'
        );
        $table->addColumn(
            QueuedRefund::KEY_CREATION_TIME,
            Table::TYPE_DATETIME,
            null,
            [
                'nullable' => false,
            ],
            'Creation Time'
        );
        $table->addColumn(
            QueuedRefund::KEY_UPDATE_TIME,
            Table::TYPE_DATETIME,
            null,
            [
                'nullable' => true,
            ],
            'Update Time'
        );
        $table->addIndex(
            $connection->getIndexName(
                QueuedRefundResource::TABLE_NAME,
                [QueuedRefund::KEY_STATUS]
            ),
            [QueuedRefund::KEY_STATUS]
        );
        $connection->createTable($table);

        // Set precision for datetime:
        $this->addPrecisionToDateTimeColumn($connection, QueuedRefund::KEY_CREATION_TIME, 'Creation Time');
        $this->addPrecisionToDateTimeColumn($connection, QueuedRefund::KEY_UPDATE_TIME, 'Update Time', true);

        return $this;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    private function addPrecisionToDateTimeColumn(
        AdapterInterface $connection,
        string $key,
        string $comment,
        bool $nullable = false
    ): void {
        $connection->query(
            sprintf(
                'ALTER TABLE `%1$s` MODIFY COLUMN `%2$s` DATETIME(6) %4$s NULL COMMENT \'%3$s\';',
                QueuedRefundResource::TABLE_NAME,
                $key,
                $comment,
                $nullable ? '' : 'NOT'
            )
        );
    }
}
