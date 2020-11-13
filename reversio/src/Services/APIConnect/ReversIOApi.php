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

namespace ReversIO\Services\APIConnect;

use Exception;
use Configuration;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use ReversIO\Config\Config;
use ReversIO\Proxy\ProxyApiClient;
use ReversIO\Repository\CategoryMapRepository;
use ReversIO\Repository\CategoryRepository;
use ReversIO\Repository\ExportedProductsRepository;
use ReversIO\Repository\Logs\Logger;
use ReversIO\Repository\Logs\LogsRepository;
use ReversIO\Repository\OrderRepository;
use ReversIO\Repository\ProductRepository;
use ReversIO\Repository\ProductsForExportRepository;
use ReversIO\Response\ReversIoResponse;
use ReversIO\Services\Brand\BrandService;
use ReversIO\Services\Cache\Cache;
use ReversIO\Services\Orders\OrdersRequestBuilder;
use ReversIO\Services\Orders\OrdersRetrieveService;
use ReversIO\Services\Product\ProductService;
use ReversIO\Services\Versions\Versions;

class ReversIOApi
{
    /** @var ProductService */
    private $productService;

    /** @var OrderRepository */
    private $orderRepository;

    /** @var LogsRepository */
    private $logsRepository;

    /** @var OrdersRetrieveService */
    private $ordersRetrieveService;

    /** @var Logger */
    private $logger;

    private $apiPublic;

    private $client;

    /** @var ProductsForExportRepository */
    private $productsExportRepository;

    /** @var CategoryMapRepository */
    private $categoryMapRepository;

    /** @var CategoryRepository */
    private $categoryRepository;

    /** @var BrandService */
    private $brandService;

    /** @var ExportedProductsRepository */
    private $exportedProductsRepository;

    /** @var Versions */
    private $version;

    /** @var ProductRepository */
    private $productRepository;

    /** @var ApiHeadersBuilder */
    private $apiHeadersBuilder;

    /** @var Cache */
    private $cache;

    private $listBrands = null;

    public function __construct(
        ProductService $productService,
        OrderRepository $orderRepository,
        LogsRepository $logsRepository,
        OrdersRetrieveService $ordersRetrieveService,
        Logger $logger,
        Token $token,
        ProxyApiClient $proxyApiClient,
        ProductsForExportRepository $productsExportRepository,
        CategoryMapRepository $categoryMapRepository,
        CategoryRepository $categoryRepository,
        BrandService $brandService,
        ExportedProductsRepository $exportedProductsRepository,
        Versions $version,
        ProductRepository $productRepository,
        ApiHeadersBuilder $apiHeadersBuilder,
        Cache $cache
    ) {
        $this->productService = $productService;
        $this->orderRepository = $orderRepository;
        $this->logsRepository = $logsRepository;
        $this->ordersRetrieveService = $ordersRetrieveService;
        $this->logger = $logger;
        $this->token = $token;
        $this->proxyApiClient = $proxyApiClient;
        $this->categoryMapRepository = $categoryMapRepository;
        $this->categoryRepository = $categoryRepository;
        $this->brandService = $brandService;
        $this->exportedProductsRepository = $exportedProductsRepository;
        $this->version = $version;
        $this->productRepository = $productRepository;

        $this->apiPublic = Configuration::get(Config::PUBLIC_KEY);
        $this->client = new \GuzzleHttp\Client();

        $this->productsExportRepository = $productsExportRepository;
        $this->apiHeadersBuilder = $apiHeadersBuilder;
        $this->cache = $cache;
    }


    /*
     * This function is retrieving a categories from the Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/RetrieveModelType?
     */
    public function getModelTypes($langIsoCode)
    {
        $response = new ReversIoResponse();

        try {
            $url = 'catalog/modelTypes';
            $headers = [
                'headers' => $this->apiHeadersBuilder->buildHeadersForGetWithLanguage($langIsoCode),
            ];

            $request = $this->proxyApiClient->get($url, $headers);
            $response->setSuccess(true);
            return $request;
        } catch (ClientException $exception) {
            $response->setSuccess(false);
            $response->setMessage($exception->getMessage());
        }
        return $response;
    }

    /**
     * This function is retrieving a brands from the Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/ListBrands?
     */
    public function getListBrands()
    {
        $response = new ReversIoResponse();

        try {
            $url = 'catalog/brands';
            $headers = [
                'headers' => $this->apiHeadersBuilder->buildHeadersForGet(),
            ];

            $request = $this->proxyApiClient->get($url, $headers);

            $response->setSuccess(true);
            return $request;
        } catch (ClientException $exception) {
            $response->setSuccess(false);
            $response->setMessage($exception->getMessage());
        }
    }

    /**
     * This function is retrieving a products from the Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/ListModels?
     */
    public function getListModels()
    {
        $response = new ReversIoResponse();

        try {
            $url = 'catalog/models';
            $headers = [
                'headers' => $this->apiHeadersBuilder->buildHeadersForGet(),
            ];

            //@todo: set the request to the response content
            $request = $this->proxyApiClient->get($url, $headers);

            $response->setSuccess(true);
            //@todo : check why the request is return not the response
            return $request;
        } catch (ServerException $exception) {
            $errorMessage = $exception->getResponse()->json()['errors'][0]['message'];

            $response->setSuccess(false);
            $response->setMessage($errorMessage);

            return $response;
        }
    }

    /**
     * This function is putting the products into the Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/CreateNewModel?
     */
    public function putProduct($productIdForInsert, $languageId)
    {
        if (Configuration::get(Config::PRODUCT_INIT_EXPORT) === "1") {
            $this->createNewBrand(Config::UNKNOWN_BRAND);

            Configuration::updateValue(Config::PRODUCT_INIT_EXPORT, 0);
        }

        $response = new ReversIoResponse();

        $rootCategory = Configuration::get('PS_ROOT_CATEGORY');

        $brands = $this->cache->getBrands();

        if (!$brands->isSuccess()) {
            return $brands;
        }

        $productReference = $this->logger->getProductReference($productIdForInsert);
        if (empty($productReference)) {
            throw new Exception('unknown product');
        }

        $brandObject = $brands->getContent();

        $manufacturer = $this->productRepository->getManufacturerNamesByProductId($productIdForInsert);
        $brandId = $this->brandService->getBrandIdByManufacturer($manufacturer, $brandObject['value']);

        if (!$brandId) {
            $createBrandResponse = $this->createNewBrand($manufacturer);

            if (!$createBrandResponse->isSuccess()) {
                return $createBrandResponse;
            }

            $brandId = $createBrandResponse->getContent()['value']['id'];

            $this->cache->updateBrandsList();
        }

        $allMappedCategories = $this->categoryMapRepository->getAllMappedCategories();
        $categoriesAndParentsIds = $this->categoryRepository->getAllCategoryAndParentIds($rootCategory);

        $productBody = $this->productService->getInfoAboutProduct(
            $brandId,
            $productIdForInsert,
            $languageId,
            $allMappedCategories,
            $categoriesAndParentsIds,
            Configuration::get(Config::DEFAULT_DIMENSIONS)
        );

        $productId = $productBody['id_product'];

        array_pop($productBody);

        try {
            $url = 'catalog/models';
            $requestHeadersAndBody = [
                'headers' => $this->apiHeadersBuilder->buildHeadersForPutAndPost(),
                'body' => json_encode($productBody),
            ];

            $request = $this->proxyApiClient->put($url, $requestHeadersAndBody);

            $this->exportedProductsRepository->insertExportedProducts(
                $productId,
                $request->getContent()['value']['id']
            );

            $this->productsExportRepository->deleteFromProductsForExport($productId);

            $response->setSuccess(true);
            $response->setContent($request->getContent()['value']['id']);
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            $errorMessage =
                [
                    'productReference' => $productBody['sKU'],
                    'message' => $exception->getResponse()->json()['errors'][0]['message']
                ];
            if (Configuration::get(Config::ENABLE_LOGGING_SETTING) !== "0") {
                $this->logger->insertProductLogs(
                    $productId,
                    $productBody['label'],
                    $errorMessage['message']
                );
            }
            $response->setSuccess(false);
            $response->setMessage($errorMessage);
        }

        return $response;
    }

    /**
     * This function is updating the products in Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/UpdateModel?
     */
    public function updateProduct($productIdForUpdate, $modelId, $languageId)
    {
        $response = new ReversIoResponse();

        $productBody = $this->productService->getInfoAboutProductForUpdate(
            $productIdForUpdate,
            $modelId,
            $languageId,
            Configuration::get(Config::DEFAULT_DIMENSIONS)
        );

        $productId = $productBody['id_product'];
        $productIdFromAPI = $productBody['modelId'];

        //@todo: change the array_pop to something like unset or smth
        array_pop($productBody);
        array_pop($productBody);
        array_pop($productBody);

        try {
            $url = 'catalog/models/'.$productIdFromAPI;
            $requestHeadersAndBody = [
                'headers' => $this->apiHeadersBuilder->buildHeadersForPutAndPost(),
                'body' => json_encode($productBody),
            ];

            $request = $this->proxyApiClient->post($url, $requestHeadersAndBody);

            $this->exportedProductsRepository->updateExportedProduct($productId);

            $this->productsExportRepository->deleteFromProductsForExport($productId);

            $response->setSuccess(true);
            $response->setContent($request->getContent()['value']['id']);
        } catch (ClientException $exception) {
            $errorMessage = $exception->getResponse()->json()['errors'][0]['message'];
            if (Configuration::get(Config::ENABLE_LOGGING_SETTING) !== "0") {
                $this->logger->insertProductLogs(
                    $productId,
                    $productBody['label'],
                    $errorMessage
                );
            }
            $response->setSuccess(false);
            $response->setMessage($errorMessage);
        }

        return $response;
    }

    public function importOrderRequest($orderBody)
    {
        $response = new ReversIoResponse();

        try {
            $url = 'orders';
            $requestHeadersAndBody = [
                'headers' => $this->apiHeadersBuilder->buildHeadersForPutAndPost(),
                'body' => json_encode($orderBody),
            ];

            $request = $this->proxyApiClient->put($url, $requestHeadersAndBody);

            $this->orderRepository->insertSuccessfullyOrNotSuccessfullyImportedOrder(
                $orderBody['orderReference'],
                1
            );

            $this->orderRepository->insertOrdersByState(
                $orderBody['orderReference'],
                Config::SYNCHRONIZED_SUCCESSFULLY_ORDERS_STATUS
            );

            $response->setSuccess(true);
            $response->setContent($request);
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            $this->orderRepository->insertSuccessfullyOrNotSuccessfullyImportedOrder(
                $orderBody['orderReference'],
                0
            );
            $errorMessage = $exception->getResponse()->json()['errors'][0]['message'];
            if (Configuration::get(Config::ENABLE_LOGGING_SETTING) !== "0") {
                $this->logger->insertOrderLogs(
                    $orderBody['orderReference'],
                    $errorMessage
                );
            }
            $this->orderRepository->insertOrdersByState(
                $orderBody['orderReference'],
                Config::SYNCHRONIZED_UNSUCCESSFULLY_ORDERS_STATUS
            );
            $response->setSuccess(false);
            $response->setMessage($errorMessage);
        }

        return $response;
    }

    /**
     * This function is retrieving the orders from the Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/RetrieveOrder?
     */
    //@todo: check NAMING ir geriau injectinti pvz ordersReference
    public function retrieveOrders()
    {
        $response = new ReversIoResponse();

        $ordersReference = $this->orderRepository->getOrdersReference();

        $ordersArray = [];

        foreach ($ordersReference as $orderReference) {
            try {
                $url = 'orders/'.$orderReference['reference'].'/status';
                $headers = [
                    'headers' => $this->apiHeadersBuilder->buildHeadersForGet(),
                ];

                $request = $this->proxyApiClient->get($url, $headers);

                $response->setSuccess(true);
                $ordersArray[] = $request->getContent();
            } catch (\GuzzleHttp\Exception\ClientException $exception) {
                $errorMessage = $exception->getResponse()->json()['errors'][0]['message'];

                $response->setSuccess(false);
                $response->setMessage($errorMessage);
            }
        }
        $response->setContent($ordersArray);
        return $response;
    }

    /**
     * This function is retrieving the orders from the Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/RetrieveOrder?
     */
    public function retrieveOrder($orderReference)
    {
        $response = new ReversIoResponse();

        try {
            $url = 'orders/'.$orderReference.'/status';
            $headers = [
                'headers' => $this->apiHeadersBuilder->buildHeadersForGet(),
            ];

            $request = $this->proxyApiClient->get($url, $headers);

            $response->setSuccess(true);
            $response->setContent($request->getContent());
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            $errorMessage = $exception->getResponse()->json()['errors'][0]['message'];

            $response->setSuccess(false);
            $response->setMessage($errorMessage);
        }
        return $response;
    }

    /**
     * This function is retrieving the orders url generated by Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/CreateSigned-inLink?
     */
    public function retrieveOrderUrl($orderId)
    {
        $response = new ReversIoResponse();

        $retrievedOrder = $this->ordersRetrieveService->getRetrievedOrder($orderId);

        $body = ['orderId' => $retrievedOrder['orderId']];

        try {
            $url = 'links';
            $requestHeadersAndBody = [
                'headers' => $this->apiHeadersBuilder->buildHeadersForPutAndPost(),
                'body' => json_encode($body),
            ];

            $request = $this->proxyApiClient->post($url, $requestHeadersAndBody);

            $this->orderRepository->insertRetrievedOrdersUrl(
                $retrievedOrder['reference'],
                $request->getContent()['value']
            );

            $response->setSuccess(true);
            $response->setContent($request);
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            $errorMessage = $exception->getResponse()->json()['errors'][0]['message'];

            $response->setSuccess(false);
            $response->setMessage($errorMessage);
        }
        return $response;
    }

    /**
     * This function is creating the new brand in Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/CreateNewBrand?
     */
    public function createNewBrand($brandName)
    {
        $response = new ReversIoResponse();

        $brandBody = [
            'name' => $brandName,
        ];

        try {
            $url = 'catalog/brands';
            $requestHeadersAndBody = [
                'headers' => $this->apiHeadersBuilder->buildHeadersForPutAndPost(),
                'body' => json_encode($brandBody),
            ];

            $request = $this->proxyApiClient->put($url, $requestHeadersAndBody);

            $response->setSuccess(true);
            $response->setContent($request->getContent());
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            $errorMessage = $exception->getResponse()->json()['errors'][0]['message'];

            $this->logger->insertBrandLogs(
                $brandName,
                $errorMessage
            );

            $response->setSuccess(false);
            $response->setMessage($errorMessage);
        }

        return $response;
    }
}
