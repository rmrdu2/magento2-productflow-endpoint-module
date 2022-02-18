<?php
namespace Productflow\Endpoint\Model\Job;

class Schedule extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
	const CACHE_TAG = 'prductflow_cron_schedule';

	protected $_cacheTag = 'prductflow_cron_schedule';

	protected $_eventPrefix = 'prductflow_cron_schedule';

	protected function _construct()
	{
		$this->_init('Productflow\Endpoint\Model\ResourceModel\Job\Schedule');
	}

	public function getIdentities()
	{
		return [self::CACHE_TAG . '_' . $this->getId()];
	}

	public function getDefaultValues()
	{
		$values = [];

		return $values;
	}
}