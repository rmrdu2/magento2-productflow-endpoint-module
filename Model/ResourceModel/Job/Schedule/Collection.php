<?php
namespace Productflow\Endpoint\Model\ResourceModel\Job\Schedule;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
	protected $_idFieldName = 'schedule_id';
	protected $_eventPrefix = 'prductflow_cron_schedule_collection';
	protected $_eventObject = 'schedule_collection';

	/**
	 * Define resource model
	 *
	 * @return void
	 */
	protected function _construct()
	{
		$this->_init('Productflow\Endpoint\Model\Job\Schedule', 'Productflow\Endpoint\Model\ResourceModel\Job\Schedule');
	}

}