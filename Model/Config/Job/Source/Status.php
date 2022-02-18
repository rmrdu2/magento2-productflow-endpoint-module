<?php
namespace Productflow\Endpoint\Model\Config\Job\Source;

use Magento\Framework\Option\ArrayInterface;

class Status implements ArrayInterface
{
    public function toOptionArray()
    {
        $result = [];
        foreach ($this->getOptions() as $value => $label) {
            $result[] = [
                 'value' => $value,
                 'label' => $label,
             ];
        }

        return $result;
    }

    public function getOptions()
    {
        return [
            0 => __('Pending'),
            1 => __('Processing'),
            2 => __('Success'),
            3 => _('Error')
        ];
    }
}