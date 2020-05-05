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

    /** @var ExportedProductsRepository */
    private $exportedProductsRepository;

    public function __construct(
        ReversIO $module,
        OrderRepository $orderRepository,
        ProductsForExportRepository $productsExportRepository,
        ReversIOApi $reversIoApiConnect,
        Cache $cache,
        ExportedProductsRepository $exportedProductsRepository
    ) {
        $this->module = $module;
        $this->orderRepository = $orderRepository;
        $this->productsExportRepository = $productsExportRepository;
        $this->reversIoApiConnect = $reversIoApiConnect;
        $this->cache = $cache;
        $this->exportedProductsRepository = $exportedProductsRepository;
    }

    public function getModelsIds($orderId, $currency)
    {
        $productsId = $this->orderRepository->getOrderedProductId($orderId);
        $orderObject = new \Order($orderId);
        $modelIdArray = [];

        foreach ($productsId as $productId) {
            $modelIdResponse = $this->getProductModelId($productId['product_id']);

            $product = new Product($productId['product_id']);

            if (!$modelIdResponse->isSuccess()) {
                throw new \Exception(sprintf('The order was not imported because one of the product with reference : 
                        %s is not valid', $modelIdResponse->getMessage()['productReference']));
            }

            $modelIdArray[] =
                [
                    'modelId' => $modelIdResponse->getContent(),
                    'price' => [
                        'amount' => $product->price,
                        'currency' => $currency,
                    ],
                    'orderLineReference' => $orderObject->reference,
                ];
        }

        return $modelIdArray;
    }

    //@todo check the naming, kad butu aisku, kad sukurs ar updatins ir comment for the  function return
    private function getProductModelId($productId)
    {
        $listModelsResponse = $this->cache->getListModels();

        if (!$listModelsResponse->isSuccess()) {
            return $listModelsResponse;
        }

        $modelIdResponse = new ReversIoResponse();

        $exportedProductId = $this->exportedProductsRepository->isProductExported($productId);


//        If $modelIdResponse is not successful, product not exported
        if ($exportedProductId === null) {
            $modelIdResponse = $this->reversIoApiConnect->putProduct($productId, Context::getContext()->language->id);

            $this->cache->updateModelList();
        } else {
            $productAddedForUpdate = $this->productsExportRepository->getProductForUpdateById($productId);

            if ($productAddedForUpdate) {
                $exportedProduct = new ReversIO\Entity\ExportedProduct($exportedProductId);

                $modelIdResponse = $this->reversIoApiConnect->updateProduct(
                    $productId,
                    $exportedProduct->reversio_product_id,
                    Context::getContext()->language->id
                );

                $this->cache->updateModelList();
            }
        }

        return $modelIdResponse;
    }
    //@todo STANISLOVAI PERVADINK
    private function getProductModelIdIfAlreadyExported($productId, $listModelsResponse)
    {
        $modelId = 0;

        $product = new Product($productId);
        $reference = $product->reference;

        //@todo: create the Object for the api values setters and getters
        foreach ($listModelsResponse->getContent()['value'] as $listModel) {
            if (isset($listModel['sku'])) {
                if ($listModel['sku'] == $reference) {
                    $modelId = $listModel['id'];

                    break;
                }
            }
        }

        //@todo: change this response for the exception and later catch the exception and in catch
        // block put the product api
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
