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
use ReversIO\Services\Decoder\Decoder;
use ReversIO\Services\APIConnect\Token;
use ReversIO\Services\Versions\Versions;
use ReversIO\Services\Getters\ColourGetter;

use ReversIO\Repository\OrderRepository;
use ReversIO\Repository\ProductRepository;
use ReversIO\Repository\BrandRepository;
use ReversIO\Repository\CategoryRepository;
use ReversIO\Repository\CategoryMapRepository;
use ReversIO\Repository\ProductsForExportRepository;
use ReversIO\Repository\ExportedProductsRepository;
use ReversIO\Repository\Logs\LogsRepository;

use ReversIO\Services\CategoryMapService;
use ReversIO\Services\Product\ProductService;
use ReversIO\Services\Product\ModelService;
use ReversIO\Services\Orders\OrdersRetrieveService;
use ReversIO\Services\Orders\OrdersRequestBuilder;
use ReversIO\Services\Orders\OrderImportService;

use ReversIO\Services\APIConnect\ReversIOApi;
use ReversIO\Services\APIConnect\ApiClient;
use ReversIO\Services\APIConnect\ApiHeadersBuilder;

use ReversIO\Factory\ClientFactory;
use ReversIO\Proxy\ProxyApiClient;
use ReversIO\Adapter\ArrayAdapter;
use ReversIO\Services\Cache\Cache;

class ReversioAjaxModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        parent::postProcess();

        if (!$this->isTokenValid()) {
            die();
        }

        try {

            // ===== Dépendances =====

            $decoder = new \ReversIO\Services\Decoder\Decoder();
            $token   = new \ReversIO\Services\APIConnect\Token($decoder);

            $versions = new \ReversIO\Services\Versions\Versions();

            $colourGetter = new \ReversIO\Services\Getters\ColourGetter();

            $orderRepository = new \ReversIO\Repository\OrderRepository($colourGetter);

            $productRepository = new \ReversIO\Repository\ProductRepository();
            $brandRepository   = new \ReversIO\Repository\BrandRepository();
            $logsRepository    = new \ReversIO\Repository\Logs\LogsRepository();

            $productsForExportRepository = new \ReversIO\Repository\ProductsForExportRepository();
            $exportedProductsRepository  = new \ReversIO\Repository\ExportedProductsRepository();

            $categoryMapRepository = new \ReversIO\Repository\CategoryMapRepository();
            $categoryRepository    = new \ReversIO\Repository\CategoryRepository();

            $categoryMapService = new \ReversIO\Services\CategoryMapService($categoryMapRepository);

            $brandService = new \ReversIO\Services\Brand\BrandService(
                $this->module,
                new \ReversIO\Adapter\ArrayAdapter()
            );

            $clientFactory = new \ReversIO\Factory\ClientFactory($versions);
            $apiClient     = new \ReversIO\Services\APIConnect\ApiClient($clientFactory);

            $proxyApiClient = new \ReversIO\Proxy\ProxyApiClient($token, $apiClient, $decoder);

            $apiHeadersBuilder = new \ReversIO\Services\APIConnect\ApiHeadersBuilder($token);

            $cache = new \ReversIO\Services\Cache\Cache($this->module);

            $ordersRetrieveService = new \ReversIO\Services\Orders\OrdersRetrieveService($this->module);

            $productImporter = new \ReversIO\Services\Product\ProductService($categoryMapService);

            $loggerService = new \ReversIO\Repository\Logs\Logger(
                $orderRepository,
                $productRepository,
                $brandRepository
            );

            $reversIoApiConnect = new \ReversIO\Services\APIConnect\ReversIOApi(
                $productImporter,
                $orderRepository,
                $logsRepository,
                $ordersRetrieveService,
                $loggerService,
                $token,
                $proxyApiClient,
                $productsForExportRepository,
                $categoryMapRepository,
                $categoryRepository,
                $brandService,
                $exportedProductsRepository,
                $versions,
                $productRepository,
                $apiHeadersBuilder,
                $cache
            );

            $ordersImport = new \ReversIO\Services\Orders\OrdersRequestBuilder(
                $orderRepository,
                $this->module,
                new \ReversIO\Services\Product\ModelService(
                    $this->module,
                    $orderRepository,
                    $productsForExportRepository,
                    $reversIoApiConnect,
                    $cache,
                    $exportedProductsRepository
                ),
                $loggerService
            );

            $orderImportService = new \ReversIO\Services\Orders\OrderImportService(
                $ordersImport,
                $reversIoApiConnect,
                $orderRepository
            );

            // ===== Logique métier =====

            $orderRepository->deleteUnsuccessfullyOrders();

            $orderId = (int) Tools::getValue('orderId');
            $orderReference = $orderRepository->getOrderReferenceById($orderId);

            $reversIoOrderImportResponse = $orderImportService->importOrder($orderId);

            if ($reversIoOrderImportResponse->isSuccess()) {
                $reversIoApiConnect->retrieveOrderUrl($orderReference);
                $reversIoOrderImportResponse->setRedirectUrl(
                    $orderRepository->getOrderUrlById($orderId)
                );
            }

            $this->ajaxRender(json_encode($reversIoOrderImportResponse));

        } catch (\Exception $e) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'message' => 'Import failed'
            ]));
        }
    }
}

