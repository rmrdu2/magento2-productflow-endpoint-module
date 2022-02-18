<?php

namespace Productflow\Endpoint\Ui\Component\Listing\Column;

class Payload extends \Magento\Ui\Component\Listing\Columns\Column
{
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                //$item['yourcolumn'] is column name

                $item['payload'] = json_encode(unserialize($item['payload'])); //Here you can do anything with actual data
            }
        }

        return $dataSource;
    }
}
