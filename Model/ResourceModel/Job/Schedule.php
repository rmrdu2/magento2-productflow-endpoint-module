<?php
namespace Productflow\Endpoint\Model\ResourceModel\Job;


class Schedule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
	
	public function __construct(
		\Magento\Framework\Model\ResourceModel\Db\Context $context
	)
	{
		parent::__construct($context);
	}
	
	protected function _construct()
	{
		$this->_init('prductflow_cron_schedule', 'schedule_id');
	}
	
}