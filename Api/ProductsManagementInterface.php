<?php

declare(strict_types=1);

namespace Productflow\Endpoint\Api;

interface ProductsManagementInterface
{
    /**
     * POST for Products api.
     *
     * @param string
     *
     * @return string
     */
    public function postProducts($storeId = null);

    /**
     * POST for Products api.
     *
     * @param mixed $product
     *
     * @return string
     */
    public function getJobcode($product);
}
