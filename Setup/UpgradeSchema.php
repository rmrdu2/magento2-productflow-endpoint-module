<?php

namespace Productflow\Endpoint\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $installer->getConnection()->addColumn(
                $installer->getTable('catalog_eav_attribute'),
                'include_in_datamodel',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => true,
                    'length' => '12,4',
                    'comment' => 'Include In Datamodel',
                    'default' => '1',
                    'after' => 'is_filterable_in_grid',
                ]
            );
        }

        $installer->endSetup();
    }
}
