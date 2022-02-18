<?php

namespace Productflow\Endpoint\Setup;

class InstallSchema implements \Magento\Framework\Setup\InstallSchemaInterface
{

	public function install(\Magento\Framework\Setup\SchemaSetupInterface $setup, \Magento\Framework\Setup\ModuleContextInterface $context)
	{
		$installer = $setup;
		$installer->startSetup();
		if (!$installer->tableExists('prductflow_cron_schedule')) {
			$table = $installer->getConnection()->newTable(
				$installer->getTable('prductflow_cron_schedule')
			)
				->addColumn(
					'schedule_id',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					null,
					[
						'identity' => true,
						'nullable' => false,
						'primary'  => true,
						'unsigned' => true,
					],
					'Schedule ID'
				)
				->addColumn(
					'job_code',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					255,
					['nullable' => false],
					'Job code'
				)
				->addColumn(
					'payload',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'2M',
					['nullable' => true],
					'Payload json for Creating/Updating'
				)
                ->addColumn(
					'messages',
					\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'2M',
					['nullable' => true],
					'Payload json for Creating/Updating'
				)
				->addColumn(
					'status',
					\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
					1,
					[],
					'Status (processing,pending,completed,error)'
				)
				->addColumn(
					'created_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
					'Started At'
				)
                ->addColumn(
					'scheduled_at',
					\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
					null,
					['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
					'Scheduled At')
                ->addColumn(
                    'executed_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                    'Executed At') 
                ->addColumn(
                    'finished_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                    'Finished At')       
				->setComment('Productflow Api Job Queue');
			$installer->getConnection()->createTable($table);

		}
		$installer->endSetup();
	}
}