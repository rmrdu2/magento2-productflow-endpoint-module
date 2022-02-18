<?php
/**
 * Created By : Rohan Hapani.
 */

namespace Productflow\Endpoint\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const XML_CONFIG_PATH = 'productflow/endpoint';

    const XML_PATH_ACCESSTOKEN = 'productflow/endpoint/access_token';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    private $storeManager;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function getConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getAccessToken()
    {
        return $this->getConfig(self::XML_PATH_ACCESSTOKEN, 1); // Pass store id in second parameter
    }

    public function getDatamodelJson()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $categoryFactory = $objectManager->create('Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');
        $storeManagerDataList = $this->storeManager->getStores();
        $categoriesArray = $categoryTree = [];
        //foreach ($storeManagerDataList as $store) {
        $store = ['code' => 'und', 'store_id' => 1];

        $categories = $categoryFactory->create()
                ->setStore($store['store_id'])
                ->addAttributeToSelect('*'); //categories from current store will be fetched

        foreach ($categories as $category) {
            $id = $category->getId();

            $categoriesArray[$store['code']][$id]['name'] = $category->getName();
            $categoriesArray[$store['code']][$id]['path'] = $category->getPath();
        }

        foreach ($categories as $category) {
            $path = explode('/', $category->getPath());
            $id = $category->getId();
            $string = '';
            foreach ($path as $pathId) {
                $string .= $categoriesArray[$store['code']][$pathId]['name'].' > ';
            }
            $string = rtrim($string, ' > ');
            $categoryTree[$store['code']][$id] = $string;
        }
        //}
        $searchCriteria = $objectManager->get("\Magento\Framework\Api\SearchCriteriaBuilder")->create();
        $searchCriteriaBuilder = $objectManager->get("\Magento\Framework\Api\SearchCriteriaBuilder");
        $filterBuilder = $objectManager->get("\Magento\Framework\Api\FilterBuilder");
        $attributeManagement = $objectManager->get("\Magento\Eav\Api\AttributeManagementInterface");
        $attributeSetRepository = $objectManager->get("\Magento\Eav\Api\AttributeSetRepositoryInterface");

        $entityCode = 'catalog_product';
        $attributeRepository = $objectManager->get("\Magento\Eav\Api\AttributeRepositoryInterface");
        $attributeInfo = $attributeRepository->getList(
            'catalog_product',
            $searchCriteria
        )->getItems();

        $searchCriteriaBuilder->addFilters(
            [
                $filterBuilder
                    ->setField('entity_type_code')
                    ->setValue($entityCode)
                    ->setConditionType('eq')
                    ->create(),
            ]
        );
        $attributeSetList = $attributeSetRepository->getList($searchCriteriaBuilder->create())->getItems();
        $attributes = [];
        $json = [];

        foreach ($attributeSetList as $attributeSet) {
            try {
                $jsonData['name'] = $attributeSet->getAttributeSetName();
                $jsonData['external_identifier'] = $attributeSet->getAttributeSetId();

                $attributes = array_merge(
                $attributes,
                $attributeManagement->getAttributes($entityCode, $attributeSet->getAttributeSetId())
            );

                foreach ($attributes as $attribute) {
                    $eavModel = $objectManager->create('Magento\Catalog\Model\ResourceModel\Eav\Attribute');
                    $attr = $eavModel->load($attribute->getId());

                    if (!$attr->getIncludeInDatamodel()) {
                        continue;
                    }

                    if ($attribute->getFrontendLabel() == '') {
                        continue;
                    }
                    $attributeData = [
                        'enrichment_level' => 2,
                        'key' => $attribute->getAttributeCode(),
                        'label' => $attribute->getFrontendLabel(),
                        'multi_value' => 0,
                        'storage_class' => $attribute->getFrontendInput() ? $attribute->getFrontendInput() : 'varchar',
                    ];
                    //foreach ($storeManagerDataList as $store) {
                    //$attributeData['translations'][$store['code']] = $attribute->getStoreLabel($store['store_id']);
                    //}
                    if ($attribute->getIsRequired()) {
                        $attributeData['enrichment_level'] = 1;
                    }
                    if ($attribute->getFrontendInput() == 'multiselect') {
                        $attributeData['multi_value'] = 1;
                    }
                    switch ($attribute->getFrontendInput()) {
                        case 'media_image':
                        case 'gallery':
                            $attributeData['storage_class'] = 'image';
                                break;
                        case 'date':
                                $attributeData['storage_class'] = 'text';
                                break;
                        case 'textarea':
                                $attributeData['storage_class'] = 'full_html';
                                break;
                        case 'select':
                        case 'multiselect':
                                $attributeData['storage_class'] = 'option';
                                break;
                        case 'swatch':
                        case 'price':
                                $attributeData['storage_class'] = 'varchar';
                                break;
                        case 'weight':
                                $attributeData['storage_class'] = 'mass';
                                break;
                        default:
                                // code...
                                break;
                    }
                    if ($attribute->getFrontendInput() == 'select' || $attribute->getFrontendInput() == 'multiselect') {
                        $options = [];

                        //foreach ($storeManagerDataList as $store) {
                        $optionArray = $attribute->setStoreId($store['store_id'])->getSource()->getAllOptions();
                        foreach ($optionArray as $key => $option) {
                            $options[] = ['value' => $option['value'],
                                                'label' => $option['label'],
                                            ];
                        }

                        $attributeData['field_options'][$store['code']] = $options;
                        //}
                    }
                    if ($attribute->getAttributeCode() == 'category_ids') {
                        $attributeData['field_options'] = $categoryTree;
                    }
                    $fields[$attribute->getAttributeCode()] = $attributeData;
                }
                $jsonData['fields'] = $fields;
                $json[] = $jsonData;
            } catch (NoSuchEntityException $exception) {
                throw new GraphQlNoSuchEntityException(__('Entity code %1 does not exist.', [$entityCode]));
            }
        }

        return $json;
    }
}
