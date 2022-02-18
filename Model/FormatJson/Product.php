<?php

namespace Productflow\Endpoint\Model\FormatJson;

use Magento\Store\Model\StoreManagerInterface;

/**
 * @api
 *
 * @since 100.0.2
 */
class Product
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string
     */
    protected $json;

    /**
     * @var array
     */
    private $requiredAttributes;

    public function __construct(
    StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
        $this->requiredAttributes = [
            'name' => '',
            'sku' => '',
            'type_id' => 'simple',
            'visibility' => 4,
            'price' => 0,
            'category_id' => 2,
            'attribute_set_id' => 4,
            'qty' => 0,
            'is_in_stock' => false,
        ];
    }

    public function formatJson($payload)
    {
        $payload = unserialize($payload);
        // print_r($payload);
        // exit;
        // echo count($payload);
        // exit;
        $convertedArray = $this->convert($payload);

        return $convertedArray;
    }

    public function convert($payloadArray)
    {
        $convertedArray = [];
        if (isset($payload[0])) {
            foreach ($payloadArray as $key => $product) {
                $convertedArray = $this->getProductBasicInformations($product);
            }
        } else {
            $convertedArray = $this->getProductBasicInformations($payloadArray);
        }

        return $convertedArray;
    }

    public function isProductExists($sku)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productFactory = $objectManager->get('\Magento\Catalog\Model\ProductFactory')->create();

        if ($product = $productFactory->getIdBySku($sku)) {
            return $product;
        }

        return false;
    }

    public function isConfigurable($product)
    {
        if (isset($product['family']) && isset($product['family']['identifier']) && $product['family']['identifier'] != '') {
            return true;
        }

        return false;
    }

    public function getFamilyIdentifier($product)
    {
        if (isset($product['family']) && isset($product['family']['identifier']) && $product['family']['identifier'] != '') {
            return $product['family']['identifier'];
        }

        return '';
    }

    public function getProductBasicInformations($product)
    {
        $sku = $product['identifiers']['sku'];
        $familyIdentifier = $this->getFamilyIdentifier($product);
        $tmpArray = [];
        $tmpArray[$sku]['is_new'] = 1;
        $tmpArray[$sku]['product'] = $this->getDefaultValues($product);
        $customAttributes = $this->getAttributeValues($product);
        if (isset($customAttributes['imageData'])) {
            $tmpArray[$sku]['product']['media_gallery_entries'] = $customAttributes['imageData'];
            unset($customAttributes['imageData']);
        }
        $tmpArray[$sku]['product']['custom_attributes'] = $customAttributes;

        if ($this->isConfigurable($product)) {
            $children[] = ['child_sku' => $sku];
            $sku = $product['family']['identifier'];
            //unset($tmpArray[$sku]);
            $tmpArray[$sku]['is_new'] = 1;
            $tmpArray[$sku]['product'] = $this->getDefaultValues($product, true);
            $tmpArray[$sku]['product']['options'] = $this->getConfigurableOptionsData($product);
            $tmpArray[$sku]['product']['children'] = $children;
        } else {
            $tmpArray[$sku]['product']['visibility'] = 4;
        }

        if ($this->isProductExists($sku)) {
            $tmpArray[$sku]['is_new'] = 0;
        }

        return $tmpArray;
    }

    public function getConfigurableOptionsData($product)
    {
        foreach ($product['values']['variation_field'] as $configurableAttribtes) {
            $configurableOptions[] = [
                'option' => [
                'attribute_id' => '142',
                'label' => 'Size',
                'values' => [
                        [
                            'value_index' => 91,
                        ],
                    ],
                ],
            ];
        }

        return $configurableOptions;
    }

    public function getChildrens($product)
    {
        return $children[$sku]['product'] = $this->getDefaultValues($product);
    }

    public function getAttributeValues($product)
    {
        $image = '';
        $customAttributeCodes = array_diff_key($product, $this->requiredAttributes);
        $customAttributeCodes = array_keys($customAttributeCodes);

        $skipKeys = ['identifiers', 'classification', 'family', 'offer'];
        $mediaTypes = ['base', 'image', 'small_image', 'thumbnail', 'media_gallery', 'gallery'];
        $customAttributes = $imgData = [];

        foreach ($customAttributeCodes as $code) {
            if (in_array($code, $skipKeys)) {
                continue;
            }
            $value = false;
            if (in_array($code, $mediaTypes)) {
                if (isset($product[$code]['values']) && count($product[$code]['values']) >= 1) {
                    foreach ($product[$code]['values'] as $value) {
                        $image = $value['value'];
                        $imgData[] = $this->getImageBase64($image, $code);
                    }
                }
            } elseif (isset($product[$code]['values']) && count($product[$code]['values']) == 1) {
                $value = strip_tags($product[$code]['values'][0]['value']);
                $customAttributes[] = [
                    'attribute_code' => $code,
                    'value' => $value,
                ];
            }
        }
        if (!empty($imgData)) {
            $customAttributes['imageData'] = $imgData;
        }

        return $customAttributes;
    }

    public function getImageBase64($url, $code)
    {
        if ($url == '') {
            return false;
        }

        $image = file_get_contents($url);
        $baseName = basename($url);
        $mimetype = $this->getRemoteMimeType($url);
        $imageData = [
            'media_type' => 'image',
            'label' => explode('.', $baseName)[0],
            'disabled' => false,
            'types' => [
                $code,
            ],
        ];

        if ($image !== false) {
            $imageData['content'] = [
                    'base64_encoded_data' => base64_encode($image),
                    'type' => $mimetype,
                    'name' => $baseName,
                ];

            return $imageData;
        }

        return false;
    }

    public function getDefaultvalues($product, $isConfigurable = false)
    {
        $requiredValues = $this->extractRequiredAttributes($product);

        return [
            'sku' => $requiredValues['sku'],
            'type_id' => !$isConfigurable ? $requiredValues['type_id'] : 'configurable',
            'attribute_set_id' => $requiredValues['attribute_set_id'],
            'visibility' => $requiredValues['visibility'],
            'price' => $requiredValues['price'],
            'name' => $requiredValues['name'],
            'extension_attributes' => [
                'category_links' => [
                    [
                        'position' => 0,
                        'category_id' => $requiredValues['category_id'],
                    ],
                ],
                'stock_item' => [
                    'qty' => $requiredValues['qty'],
                    'is_in_stock' => $requiredValues['is_in_stock'],
                ],
            ],
        ];
    }

    public function extractRequiredAttributes($product)
    {
        $requiredValues = [];

        foreach ($this->requiredAttributes as $attributecode => $value) {
            switch ($attributecode) {
                case 'name':
                    $value = $product['identifiers']['sku'];
                    break;
                case 'sku':
                    $value = $product['identifiers']['sku'];
                    break;
                case 'attribute_set_id':
                    $value = (isset($product['classification'])) ? $product['classification']['external_identifier'] : 4;
                    break;
                case 'qty':
                    $value = (isset($product['offer']) && isset($product['offer']['qty_sellable'])) ? $product['offer']['qty_sellable'] : 0;
                    break;
                case 'is_in_stock':
                    $value = (isset($product['offer']) && $product['offer']['qty_sellable'] > 0) ? true : false;
                    break;
                case 'price':
                    $value = (isset($product['offer']) && isset($product['offer']['price'])) ? $product['offer']['price'] : 0;
                    break;
            }

            if (isset($product[$attributecode]) && isset($product[$attributecode]['values'])) {
                if (isset($product[$attributecode]['values']) && count($product[$attributecode]['values']) == 1) {
                    $requiredValues[$attributecode] = $product[$attributecode]['values'][0]['value'];
                    if ($attributecode == 'name') {
                        $requiredValues[$attributecode] = strip_tags($product[$attributecode]['values'][0]['value']);
                    }
                }
            } else {
                $requiredValues[$attributecode] = $value;
            }
        }

        return $requiredValues;
    }

    public function getRemoteMimeType($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);

        // get the content type
        return curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    }
}
