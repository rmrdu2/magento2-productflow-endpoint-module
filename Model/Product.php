<?php

namespace Productflow\Endpoint\Model;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\StoreManagerInterface;
use Productflow\Endpoint\Model\FormatJson\Product as FormatJson;

/**
 * @api
 *
 * @since 100.0.2
 */
class Product
{
    /**
     * @var FormatJson
     */
    private $formatFactory;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Curl
     */
    protected $curlClient;

    /**
     * @var string
     */
    protected $accessToken = '';

    /**
     * @var string
     */
    protected $baseUrl = '';

    /**
     * @var string
     */
    protected $apiUrl = '';

    /**
     * @var string
     */
    protected $configurableProductApiUrl = '';

    /**
     * @var string
     */
    protected $storeId;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
    string $storeId,
    Curl $curl,
    StoreManagerInterface $storeManager,
    JsonFactory $resultJsonFactory,
    FormatJson $formatFactory)
    {
        $this->curlClient = $curl;
        $this->storeManager = $storeManager;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $this->formatFactory = $formatFactory;
        $storeId = $storeId ? $storeId : $this->storeManager->getStore()->getStoreId();
        $store = $this->storeManager->getStore($storeId)->getCode();

        $this->apiUrl = "rest/{$store}/V1/products";
        $this->configurableProductApiUrl = "rest/{$store}/V1/configurable-products";
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->baseUrl.$this->apiUrl;
    }

    /**
     * @return string
     */
    public function getConfigurableApiUrl()
    {
        return $this->baseUrl.$this->configurableProductApiUrl;
    }

    /**
     * Gets partners json.
     *
     * @return array
     */
    public function postProducts($job)
    {
        try {
            $productData = $this->formatFactory->formatJson($job->getPayload());

            foreach ($productData as $param) {
                $isNew = $param['is_new'];
                unset($param['is_new']);

                if (!$isNew) {
                    $response = $this->processUpdateProductRequest($param);
                } else {
                    if ($param['product']['type_id'] == 'configurable') {
                        $sku = $param['product']['sku'];
                        $options['sku'] = $sku;
                        $children['sku'] = $sku;
                        $children['children'] = $param['product']['children'];
                        $options['options'] = $param['product']['options'];

                        unset($param['product']['children']);
                        unset($param['product']['options']);
                    }
                    $response = $this->processCreateProductRequest($param);

                    if (!empty($options)) {
                        $this->processDefineConfigurableOptions($options);
                    }
                    if (!empty($children)) {
                        $this->processAssignChilds($children);
                    }
                }
                if (!isset($response['id'])) {
                    $errors[] = $response['message'];
                }
            }

            if (!empty($errors)) {
                $return = ['status' => 3, 'message' => implode(',', $errors)];
            } else {
                $return = ['status' => 2, 'message' => 'Success'];
            }
        } catch (\Exception $e) {
            $return = ['status' => 3, 'message' => $e->getMessage()];
        }

        $job->setStatus($return['status']);
        $job->setMessages($return['message']);
        $job->save();
    }

    /**
     * @return Curl
     */
    public function getCurlClient()
    {
        return $this->curlClient;
    }

    public function getHeaders()
    {
        $token = $this->getAccessToken();
        $headers = ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$token];

        return $headers;
    }

    public function getAccessToken()
    {
        return $this->getHelper()->getAccessToken();
    }

    public function getHelper()
    {
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $object_manager->get('\Productflow\Endpoint\Helper\Data');

        return $helper;
    }

    public function processCreateProductRequest($param)
    {
        $apiUrl = $this->getApiUrl();
        $curl = $this->getCurlClient();
        $token = $this->getAccessToken();
        //set curl options
        $curl->setOption(CURLOPT_HEADER, 0);
        $curl->setOption(CURLOPT_TIMEOUT, 60);
        $curl->setOption(CURLOPT_RETURNTRANSFER, true);
        //set curl header
        $curl->addHeader('Content-Type', 'application/json');
        $curl->addHeader('Authorization', 'Bearer '.$token);
        //post request with url and data
        $curl->post($apiUrl, json_encode($param));
        //read response
        $response = $curl->getBody();
        $response = json_decode($this->getCurlClient()->getBody(), true);

        return $response;
    }

    public function processUpdateProductRequest($param)
    {
        $curl = $this->getCurlClient();
        $token = $this->getAccessToken();
        $sku = $param['product']['sku'];
        $apiUrl = $this->getApiUrl().'/'.$sku;
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer '.$token, ]
        );
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        curl_close($ch);

        return $response;
    }

    public function processDefineConfigurableOptions($param)
    {
        $sku = $param['sku'];
        $options = $param['options'];
        $configurableProductApiUrl = $this->getConfigurableApiUrl().'/'.$sku.'/options';
        $curl = $this->getCurlClient();
        $token = $this->getAccessToken();
        //set curl options
        $curl->setOption(CURLOPT_HEADER, 0);
        $curl->setOption(CURLOPT_TIMEOUT, 60);
        $curl->setOption(CURLOPT_RETURNTRANSFER, true);
        //set curl header
        $curl->addHeader('Content-Type', 'application/json');
        $curl->addHeader('Authorization', 'Bearer '.$token);

        //post request with url and data
        foreach ($options as $option) {
            $curl->post($configurableProductApiUrl, json_encode($option));
            $response = $curl->getBody();
            $response = json_decode($this->getCurlClient()->getBody(), true);
            $return[] = $response;
        }

        return true;
    }

    public function processAssignChilds($param)
    {
        $sku = $param['sku'];
        $children = $param['children'];

        $configurableProductApiUrl = $this->getConfigurableApiUrl().'/'.$sku.'/child';

        $curl = $this->getCurlClient();
        $token = $this->getAccessToken();
        //set curl options
        $curl->setOption(CURLOPT_HEADER, 0);
        $curl->setOption(CURLOPT_TIMEOUT, 60);
        $curl->setOption(CURLOPT_RETURNTRANSFER, true);
        //set curl header
        $curl->addHeader('Content-Type', 'application/json');
        $curl->addHeader('Authorization', 'Bearer '.$token);
        //post request with url and data
        foreach ($children as $child) {
            $curl->post($configurableProductApiUrl, json_encode($child));
            $response = $curl->getBody();
            $response = json_decode($this->getCurlClient()->getBody(), true);
            $return[] = $response;
        }
    }

    public function processUploadMedia($param)
    {
        $sku = $param['sku'];
        $options = $param['mediaGalleryImagaes'];
        print_r($options);
        exit;
        $apiUrl = $this->getApiUrl().'/'.$sku.'/media';
        $curl = $this->getCurlClient();
        $token = $this->getAccessToken();
        //set curl options
        $curl->setOption(CURLOPT_HEADER, 0);
        $curl->setOption(CURLOPT_TIMEOUT, 60);
        $curl->setOption(CURLOPT_RETURNTRANSFER, true);
        //set curl header
        $curl->addHeader('Content-Type', 'application/json');
        $curl->addHeader('Authorization', 'Bearer '.$token);

        $curl->post($apiUrl, json_encode($option));
        $response = $curl->getBody();
        $response = json_decode($this->getCurlClient()->getBody(), true);
        //post request with url and data

        return true;
    }
}
