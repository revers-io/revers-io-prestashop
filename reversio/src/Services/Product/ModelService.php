<?php
/**
 *Copyright (c) 2019 Revers.io
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author revers.io
 * @copyright Copyright (c) permanent, Revers.io
 * @license   Revers.io
 * @see       /LICENSE
 */

namespace ReversIO\Services\Product;

use Context;
use Product;
use ReversIO;
use ReversIO\Repository\ExportedProductsRepository;
use ReversIO\Repository\OrderRepository;
use ReversIO\Repository\ProductsForExportRepository;
use ReversIO\Response\ReversIoResponse;
use ReversIO\Services\APIConnect\ReversIOApi;
use ReversIO\Services\Cache\Cache;
use ReversIO\Services\Product\ProductService;

class ModelService
{
    /**
     * @var ReversIO
     */
    private $module;

    /** @var OrderRepository */
    private $orderRepository;

    /** @var ProductsForExportRepository */
    private $productsExportRepository;

    /** List of product from Revers.io */
    private $listModels = null;

    /** @var ReversIOApi */
    private $reversIoApiConnect;

    /** @var Cache */
    private $cache;

    /** @var ProductService */
    private $productService;

    /** @var ExportedProductsRepository */
    private $exportedProductsRepository;

    public function __construct(
        ReversIO $module,
        OrderRepository $orderRepository,
        ProductsForExportRepository $productsExportRepository,
        ReversIOApi $reversIoApiConnect,
        Cache $cache,
        ExportedProductsRepository $exportedProductsRepository,
        ProductService $productService
    ) {
        $this->module = $module;
        $this->orderRepository = $orderRepository;
        $this->productsExportRepository = $productsExportRepository;
        $this->reversIoApiConnect = $reversIoApiConnect;
        $this->cache = $cache;
        $this->exportedProductsRepository = $exportedProductsRepository;
        $this->productService = $productService;
    }

    public function getModelsIds($orderId, $currency)
    {
        $orderProductDetails = $this->orderRepository->getOrderProductDetails($orderId);
        $modelIdArray = [];

        foreach ($orderProductDetails as $orderProductDetail) {
            $id_order_detail = $orderProductDetail['id_order_detail'];
            $quantity = $orderProductDetail['product_quantity'];
            $quantityRefunded = $orderProductDetail['product_quantity_refunded'];
            $quantityReturned = $orderProductDetail['product_quantity_return'];
            $returnableQuantity = $quantity - $quantityRefunded - $quantityReturned;

            if($quantity < 1 || $returnableQuantity < 1) {
                continue;
            }

            $unitPaidPrice = $orderProductDetail['total_price_tax_incl'] / (float) $quantity;

            if ( $unitPaidPrice <= 0 ) {
                continue;
            }

            $modelIdResponse = $this->getProductModelId($orderProductDetail);
            if (!$modelIdResponse->isSuccess()) {
                throw new \Exception(sprintf('The order was not imported because one of the product with reference : 
                        %s is not valid', $modelIdResponse->getMessage()['productReference']));
            }

            for ($i = 0; $i < $returnableQuantity; $i++) {
                $modelIdArray[] =
                    [
                        'modelId' => $modelIdResponse->getContent(),
                        'price' => [
                                'amount' => $unitPaidPrice,
                            'currency' => $currency,
                        ],
                        'orderLineReference' => $id_order_detail . "-" . $i,
                    ];
            }
        }

        return $modelIdArray;
    }

    private function getProductModelId($productDetail)
    {
        $productId = $productDetail['product_id'];
        $product = new Product($productId);

        $sku = $this->productService->getSkuFromProduct($product, $productDetail);
        
        $listModelsResponse = $this->cache->getListModels();

        if (!$listModelsResponse->isSuccess()) {
            return $listModelsResponse;
        }

        $existingModelIdInReversIOResponse = $this->getProductModelIdIfAlreadyExported($product, $listModelsResponse, $productDetail);

        $modelIdResponse = new ReversIoResponse();

        if (!$existingModelIdInReversIOResponse->isSuccess()) { //Product not exported. Inserting
            $modelIdResponse = $this->reversIoApiConnect->putProduct($productId, Context::getContext()->language->id, $productDetail);
            $this->cache->updateModelList();

            return $modelIdResponse;
        } 
        else  { //Product exported. Updating
            $exportedModelId = $existingModelIdInReversIOResponse->getContent();

            $modelIdResponse = $this->reversIoApiConnect->updateProduct(
                $productId,
                $exportedModelId,
                Context::getContext()->language->id,
                $productDetail
            );
        }

        return $modelIdResponse;
    }

    private function getProductModelIdIfAlreadyExported($product, $listModelsResponse, $productDetail)
    {
        $modelId = 0;

        $sku = $this->productService->getSkuFromProduct($product, $productDetail);

        foreach ($listModelsResponse->getContent()['value'] as $listModel) {
            if (isset($listModel['sku'])) {
                if ($listModel['sku'] == $sku) {
                    $modelId = $listModel['id'];

                    break;
                }
            }
        }

        $response = new ReversIoResponse();

        if ($modelId) {
            $response->setSuccess(true);
            $response->setContent($modelId);
        } else {
            $response->setSuccess(false);
        }

        return $response;
    }
}
