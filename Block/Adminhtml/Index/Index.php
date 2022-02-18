<?php

namespace Productflow\Endpoint\Block\Adminhtml\Index;

class Index extends \Magento\Backend\Block\Widget\Grid\Container
{

    protected function _construct()
	{
		$this->_controller = 'productflow_index';
		$this->_blockGroup = 'Productflow_Endpoint';
		$this->_headerText = __('Productflow Payload Queue');
		parent::_construct();
	}


}
