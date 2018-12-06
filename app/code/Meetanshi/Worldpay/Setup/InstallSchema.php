<?php

namespace Meetanshi\Worldpay\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        $table = $installer->getConnection()->newTable($installer->getTable('meetanshi_worldpay_saved_cards'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                [
                    'auto_increment' => true,
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ],
                'Id'
            )->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'unsigned' => true,
                    'nullable' => false
                ],
                'Customer Id'
            )->addColumn(
                'token',
                Table::TYPE_TEXT,
                255,
                [],
                'Token'
            );

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
