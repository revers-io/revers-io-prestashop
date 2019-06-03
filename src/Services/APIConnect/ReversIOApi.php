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
use ReversIO\Repository\ProductsForExportRepository;
use ReversIO\Response\ReversIoResponse;
use ReversIO\Services\Brand\BrandService;
use ReversIO\Services\Orders\OrdersImporter;
use ReversIO\Services\Orders\OrdersRetrieveService;
use ReversIO\Services\Product\ProductService;

class ReversIOApi
{
    /** @var ProductService */
    private $productService;

    /** @var OrdersImporter */
    private $ordersImportService;

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

    public function __construct(
        ProductService $productService,
        OrdersImporter $ordersImportService,
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
        ExportedProductsRepository $exportedProductsRepository
    ) {
        $this->productService = $productService;
        $this->ordersImportService = $ordersImportService;
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

        $this->apiPublic = Configuration::get(Config::PUBLIC_KEY);
        $this->client = new \GuzzleHttp\Client();

        $this->productsExportRepository = $productsExportRepository;
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
                'headers' => [
                    'Accept-Language' => $langIsoCode,
                    'Authorization' => 'Bearer '.$this->token->getToken()->getContent()['value'],
                    'Ocp-Apim-Subscription-Key' => $this->apiPublic,
                ]
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
                'headers' => [
                    'Authorization' => 'Bearer '.$this->token->getToken()->getContent()['value'],
                    'Ocp-Apim-Subscription-Key' => $this->apiPublic,
                ]
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
                'headers' => [
                    'Authorization' => 'Bearer '.$this->token->getToken()->getContent()['value'],
                    'Ocp-Apim-Subscription-Key' => $this->apiPublic,
                ]
            ];

            $request = $this->proxyApiClient->get($url, $headers);

            $response->setSuccess(true);
            return $request;
        } catch (ServerException $exception) {
            $response->setSuccess(false);
            $response->setMessage($exception->getResponse()->json()['errors'][0]['message']);
            throw $exception;
        }
    }

    /**
     * This function is putting the products into the Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/CreateNewModel?
     */
    public function putProducts($productForInsert, $languageId)
    {
        $response = new ReversIoResponse();

        $rootCategory = Configuration::get('PS_ROOT_CATEGORY');

        $brands = $this->getListBrands();

        if (!$brands->isSuccess()) {
            return $brands;
        }

        $brandObject = $brands->getContent();

        $unknownBrands = $this->brandService->getUnknownBrandsByProductIds($productForInsert, $brandObject['value']);
// TODO: need better solution for this
        if (!empty($unknownBrands)) {
            foreach ($unknownBrands as $unknownBrand) {
                $this->createNewBrand($unknownBrand);
            }

            $brands = $this->getListBrands();

            if (!$brands->isSuccess()) {
                return $brands;
            }

            $brandObject = $brands->getContent();
        }

        $allMappedCategories = $this->categoryMapRepository->getAllMappedCategories();
        $categoriesAndParentsIds = $this->categoryRepository->getAllCategoryAndParentIds($rootCategory);

        $productsBody = $this->productService->getInfoAboutProduct(
            $brandObject,
            $productForInsert,
            $languageId,
            $allMappedCategories,
            $categoriesAndParentsIds
        );

        foreach ($productsBody as $productBody) {
            $productId = $productBody['id_product'];

            array_pop($productBody);

            try {
                $url = 'catalog/models';
                $requestHeadersAndBody = [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->token->getToken()->getContent()['value'],
                        'Content-Type' => 'application/json',
                        'Ocp-Apim-Subscription-Key' => $this->apiPublic,
                    ],
                    'body' => json_encode($productBody),
                ];

                $request = $this->proxyApiClient->put($url, $requestHeadersAndBody);

                $this->exportedProductsRepository->insertExportedProducts($productId);

                $this->productsExportRepository->deleteFromProductsForExport($productId);

                $response->setSuccess(true);
                $response->setContent($request);
            } catch (\GuzzleHttp\Exception\ClientException $exception) {
                if (Configuration::get(Config::ENABLE_LOGGING_SETTING) !== "0") {
                    $this->logger->insertProductLogs(
                        $productId,
                        $productBody['label'],
                        $exception->getResponse()->json()['errors'][0]['message']
                    );

                    $response->setSuccess(false);
                    $response->setMessage($exception->getResponse()->json()['errors'][0]['message']);
                }
            }
        }

        return $response;
    }

    /**
     * This function is updating the products in Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/UpdateModel?
     */
    public function updateProducts($productForUpdate, $languageId)
    {
        $response = new ReversIoResponse();

        $models = $this->getListModels();

        $modelsObject = $models->getContent();

        $productsBody =
            $this->productService->getInfoAboutProductForUpdate($modelsObject, $productForUpdate, $languageId);

        foreach ($productsBody as $productBody) {
            $productId = $productBody['id_product'];
            $productIdFromAPI = $productBody['modelId'];
            $productName = $productBody['name'];

            array_pop($productBody);
            array_pop($productBody);
            array_pop($productBody);

            try {
                $url = 'catalog/models/'.$productIdFromAPI;
                $requestHeadersAndBody = [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->token->getToken()->getContent()['value'],
                        'Content-Type' => 'application/json',
                        'Ocp-Apim-Subscription-Key' => $this->apiPublic,
                    ],
                    'body' => json_encode($productBody),
                ];

                $request = $this->proxyApiClient->post($url, $requestHeadersAndBody);

                $this->exportedProductsRepository->updateExportedProduct($productId);

                $this->productsExportRepository->deleteFromProductsForExport($productId);

                $response->setSuccess(true);
                $response->setContent($request);
            } catch (ClientException $exception) {
                if (Configuration::get(Config::ENABLE_LOGGING_SETTING) !== "0") {
                    $this->logger->insertProductLogs(
                        $productId,
                        $productName,
                        $exception->getResponse()->json()['errors'][0]['message']
                    );

                    $response->setSuccess(false);
                    $response->setMessage($exception->getResponse()->json()['errors'][0]['message']);
                }
            }
        }
        return $response;
    }

    /**
     * This function is importing the orders into the Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/ImportOrder?
     */
    public function importOrders()
    {
        $response = new ReversIoResponse();

        $ordersBody = $this->ordersImportService->getOrdersInformationForImport();

        foreach ($ordersBody as $orderBody) {
            try {
                $url = 'orders';
                $requestHeadersAndBody = [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->token->getToken()->getContent()['value'],
                        'Content-Type' => 'application/json',
                        'Ocp-Apim-Subscription-Key' => $this->apiPublic,
                    ],
                    'body' => json_encode($orderBody),
                ];

                $request = $this->proxyApiClient->put($url, $requestHeadersAndBody);

                $this->orderRepository->insertSuccessfullyImportedOrder($orderBody['orderReference']);

                $this->orderRepository->insertOrdersByState(
                    $orderBody['orderReference'],
                    Config::SYNCHRONIZED_SUCCESSFULLY_ORDERS_STATUS
                );

                $response->setSuccess(true);
                $response->setContent($request);
            } catch (\GuzzleHttp\Exception\ClientException $exception) {
                if (Configuration::get(Config::ENABLE_LOGGING_SETTING) !== "0") {
                    $this->logger->insertOrderLogs(
                        $orderBody['orderReference'],
                        $exception->getResponse()->json()['errors'][0]['message']
                    );

                    $this->orderRepository->insertOrdersByState(
                        $orderBody['orderReference'],
                        Config::SYNCHRONIZED_UNSUCCESSFULLY_ORDERS_STATUS
                    );

                    $response->setSuccess(false);
                    $response->setMessage($exception->getResponse()->json()['errors'][0]['message']);
                }
            }
        }
        return $response;
    }

    /**
     * This function is retrieving the orders from the Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/RetrieveOrder?
     */
    public function retrieveOrder()
    {
        $response = new ReversIoResponse();

        $ordersReference = $this->orderRepository->getOrdersReference();

        $ordersArray = [];

        foreach ($ordersReference as $orderReference) {
            try {
                $url = 'orders/'.$orderReference['reference'].'/status';
                $headers = [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->token->getToken()->getContent()['value'],
                        'Ocp-Apim-Subscription-Key' => $this->apiPublic,
                    ]
                ];

                $request = $this->proxyApiClient->get($url, $headers);

                $response->setSuccess(true);
                $ordersArray[] = $request->getContent();
            } catch (\GuzzleHttp\Exception\ClientException $exception) {
                $response->setSuccess(false);
                $response->setMessage($exception->getResponse()->json()['errors'][0]['message']);
            }
        }
        $response->setContent($ordersArray);
        return $response;
    }

    /**
     * This function is retrieving the orders url generated by Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/CreateSigned-inLink?
     */
    public function retrieveOrderUrl()
    {
        $response = new ReversIoResponse();

        $retrievedOrders = $this->ordersRetrieveService->getRetrievedOrders();

        foreach ($retrievedOrders as $retrievedOrder) {
            $body = ['orderId' => $retrievedOrder['orderId']];

            try {
                $url = 'links';
                $requestHeadersAndBody = [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->token->getToken()->getContent()['value'],
                        'Content-Type' => 'application/json',
                        'Ocp-Apim-Subscription-Key' => $this->apiPublic,
                    ],
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
                $response->setSuccess(false);
                $response->setMessage($exception->getResponse()->json()['errors'][0]['message']);
            }
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
                'headers' => [
                    'Authorization' => 'Bearer '.$this->token->getToken()->getContent()['value'],
                    'Content-Type' => 'application/json',
                    'Ocp-Apim-Subscription-Key' => $this->apiPublic,
                ],
                'body' => json_encode($brandBody),
            ];

            $request = $this->proxyApiClient->put($url, $requestHeadersAndBody);

            $response->setSuccess(true);
            $response->setContent($request);
        } catch (\GuzzleHttp\Exception\ClientException $exception) {
            $response->setSuccess(false);
            $response->setMessage($exception->getResponse()->json()['errors'][0]['message']);
        }

        return $response;
    }
}
