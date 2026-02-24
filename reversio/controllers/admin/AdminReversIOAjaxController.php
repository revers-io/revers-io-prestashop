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

use ReversIO\Config\Config;
use ReversIO\Controller\ReversIOAbstractAdminController;
use ReversIO\Repository\CategoryMapRepository;
use ReversIO\Services\CategoryMapService;
use ReversIO\Services\Decoder\Decoder;
use ReversIO\Services\APIConnect\Token;
use ReversIO\Services\APIConnect\ReversIOApi;
use ReversIO\Services\Cache\Cache;
use ReversIO\Services\Versions\Versions;
use ReversIO\Factory\ClientFactory;
use ReversIO\Services\APIConnect\ApiClient;
use ReversIO\Proxy\ProxyApiClient;
use ReversIO\Services\APIConnect\ApiHeadersBuilder;
use ReversIO\Services\Product\ProductService;
use ReversIO\Repository\OrderRepository;
use ReversIO\Repository\ProductRepository;
use ReversIO\Repository\CategoryRepository;
use ReversIO\Repository\ExportedProductsRepository;
use ReversIO\Repository\ProductsForExportRepository;
use ReversIO\Repository\Logs\LogsRepository;
use ReversIO\Services\Orders\OrdersRetrieveService;
use ReversIO\Services\Getters\ColourGetter;
use ReversIO\Services\Brand\BrandService;
use ReversIO\Services\Orders\OrderImportService;
use ReversIO\Services\Orders\OrdersRequestBuilder;
use ReversIO\Repository\BrandRepository;
use ReversIO\Services\Product\ModelService;


class AdminReversIOAjaxController extends ReversIOAbstractAdminController
{
    public function ajaxProcessImportOrdersToReversIo()
    {
        if (Tools::getValue('token_bo') !== Tools::getAdminTokenLite('AdminReversIOAjaxController')) {
            die();
        }

        $this->updateValues(
            Tools::getValue('orders_status'),
            Tools::getValue('orders_date_from'),
            Tools::getValue('orders_date_to')
        );

        // --- REPOS ---
        $colourGetter = new ColourGetter();
        $orderRepository = new OrderRepository($colourGetter);

        // --- CLEAN ---
        $orderRepository->deleteUnsuccessfullyOrders();

        // --- CORE STACK ---
        $decoder = new Decoder();
        $token = new Token($decoder);
        $versions = new Versions();

        $clientFactory = new ClientFactory($versions);
        $apiClient = new ApiClient($clientFactory);
        $proxyApiClient = new ProxyApiClient($token, $apiClient, $decoder);
        $apiHeadersBuilder = new ApiHeadersBuilder($token);

        // --- OTHER REPOS ---
        $logsRepository = new LogsRepository();
        $categoryRepository = new CategoryRepository();
        $categoryMapRepository = new CategoryMapRepository();
        $productsForExportRepository = new ProductsForExportRepository();
        $exportedProductsRepository = new ExportedProductsRepository();
        $productRepository = new ProductRepository();
        $brandRepository = new BrandRepository();

        // --- SERVICES ---
        $categoryMapService = new CategoryMapService($categoryMapRepository);
        $productImporter = new ProductService($categoryMapService);
        $brandService = new BrandService($this->module, new \ReversIO\Adapter\ArrayAdapter());

        $loggerService = new \ReversIO\Repository\Logs\Logger(
            $orderRepository,
            $productRepository,
            $brandRepository
        );

        $ordersRetrieveService = new OrdersRetrieveService();

        $reversIoApiConnect = new ReversIOApi(
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
            null
        );
        $cache = new \ReversIO\Services\Cache\Cache($reversIoApiConnect);
        $reversIoApiConnect->setCache($cache);
        $ordersRetrieveService->setApi($reversIoApiConnect);

        $modelService = new ModelService(
            $this->module,
            $orderRepository,
            $productsForExportRepository,
            $reversIoApiConnect,
            new Cache($reversIoApiConnect),
            $exportedProductsRepository
        );

        // --- REQUEST BUILDER ---
        $ordersImport = new OrdersRequestBuilder(
            $orderRepository,
            $this->module,
            $modelService,
            $loggerService
        );

        // ✅ SERVICE MANQUANT
        $orderImportService = new OrderImportService(
            $ordersImport,
            $reversIoApiConnect,
            $orderRepository
        );

        // --- LOGIC ---
        $sumFailed = 0;
        $sumImported = 0;

        $reversIoOrderImportResponse = $orderImportService->importOrders();

        $sumFailed += $reversIoOrderImportResponse->getTotalFailed();
        $sumImported += $reversIoOrderImportResponse->getTotalImported();

        while (!$reversIoOrderImportResponse->getImportFinished()) {
            $reversIoOrderImportResponse = $orderImportService->importOrders();
            $sumFailed += $reversIoOrderImportResponse->getTotalFailed();
            $sumImported += $reversIoOrderImportResponse->getTotalImported();
        }

        $jsonData = [
            'totalImported' => $sumImported,
            'totalFailed' => $sumFailed,
            'importFinished' => $reversIoOrderImportResponse->getImportFinished(),
            'totalSum' => $sumFailed + $sumImported,
        ];

        $this->ajaxRender(json_encode($jsonData));
    }

    public function ajaxProcessImportOrderToReversIo()
    {
        if (Tools::getValue('token_bo') !== Tools::getAdminTokenLite('AdminReversIOAjaxController')) {
            die();
        }

        try {

            // --- CORE DEPENDENCIES ---
            $decoder = new \ReversIO\Services\Decoder\Decoder();
            $token = new \ReversIO\Services\APIConnect\Token($decoder);
            $versions = new \ReversIO\Services\Versions\Versions();
            //$cache = new \ReversIO\Services\Cache\Cache($this->module);

            // --- REPOSITORIES ---
            $colourGetter = new \ReversIO\Services\Getters\ColourGetter();
            $orderRepository = new \ReversIO\Repository\OrderRepository($colourGetter);
            $productRepository = new \ReversIO\Repository\ProductRepository();
            $brandRepository = new \ReversIO\Repository\BrandRepository();
            $logsRepository = new \ReversIO\Repository\Logs\LogsRepository();
            $categoryRepository = new \ReversIO\Repository\CategoryRepository();
            $categoryMapRepository = new \ReversIO\Repository\CategoryMapRepository();
            $productsForExportRepository = new \ReversIO\Repository\ProductsForExportRepository();
            $exportedProductsRepository = new \ReversIO\Repository\ExportedProductsRepository();

            // --- SERVICES ---
            $categoryMapService = new \ReversIO\Services\CategoryMapService($categoryMapRepository);
            $productImporter = new \ReversIO\Services\Product\ProductService($categoryMapService);
            $brandService = new \ReversIO\Services\Brand\BrandService($this->module, new \ReversIO\Adapter\ArrayAdapter());
            $ordersRetrieveService = new OrdersRetrieveService();
            $loggerService = new \ReversIO\Repository\Logs\Logger(
                $orderRepository,
                $productRepository,
                $brandRepository
            );

            // --- API STACK ---
            $clientFactory = new \ReversIO\Factory\ClientFactory($versions);
            $apiClient = new \ReversIO\Services\APIConnect\ApiClient($clientFactory);
            $proxyApiClient = new \ReversIO\Proxy\ProxyApiClient($token, $apiClient, $decoder);
            $apiHeadersBuilder = new \ReversIO\Services\APIConnect\ApiHeadersBuilder($token);

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
                null
            );
            $cache = new Cache($reversIoApiConnect);
            // --- MODEL SERVICE ---
            $modelService = new \ReversIO\Services\Product\ModelService(
                $this->module,
                $orderRepository,
                $productsForExportRepository,
                $reversIoApiConnect,
                $cache,
                $exportedProductsRepository
            );

            // --- ORDER IMPORT ---
            $ordersImport = new \ReversIO\Services\Orders\OrdersRequestBuilder(
                $orderRepository,
                $this->module,
                $modelService,
                $loggerService
            );

            $orderImportService = new \ReversIO\Services\Orders\OrderImportService(
                $ordersImport,
                $reversIoApiConnect,
                $orderRepository
            );

            // --- LOGIC ---
            $orderRepository->deleteUnsuccessfullyOrders();

            $orderId = (int) Tools::getValue('orderId');
            $orderReference = $orderRepository->getOrderReferenceById($orderId);

            $reversIoOrderImportResponse = $orderImportService->importOrder($orderId);

            if ($reversIoOrderImportResponse->isSuccess()) {
                $reversIoApiConnect->retrieveOrderUrl($orderReference);
            }

            $this->ajaxRender(json_encode($reversIoOrderImportResponse));

        } catch (\Exception $e) {
            $this->ajaxRender(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
    }


    public function ajaxProcessDisplayCategories()
    {
        $categoryMapRepository = new CategoryMapRepository();
        $categoryMapService = new CategoryMapService($categoryMapRepository);

        // --- Core deps ---
        $decoder = new Decoder();
        $token = new Token($decoder);
        $versions = new Versions();

        // Cache sans API au départ


        // --- Repositories ---
        $colourGetter = new ColourGetter();
        $orderRepository = new OrderRepository($colourGetter);
        $productRepository = new ProductRepository();
        $categoryRepository = new CategoryRepository();
        $exportedProductsRepository = new ExportedProductsRepository();
        $productsForExportRepository = new ProductsForExportRepository();
        $logsRepository = new LogsRepository();

        // --- Services ---
        $productImporter = new ProductService($categoryMapService);
        $brandService = new BrandService($this->module, new \ReversIO\Adapter\ArrayAdapter());

        // OrdersRetrieveService sans API au départ
        $ordersRetrieveService = new OrdersRetrieveService();

        $loggerService = new \ReversIO\Repository\Logs\Logger(
            $orderRepository,
            $productRepository,
            new \ReversIO\Repository\BrandRepository()
        );

        // --- API stack ---
        $clientFactory = new ClientFactory($versions);
        $apiClient = new ApiClient($clientFactory);
        $proxyApiClient = new ProxyApiClient($token, $apiClient, $decoder);
        $apiHeadersBuilder = new ApiHeadersBuilder($token);

        // --- FINAL API OBJECT ---
        $reversIoApiConnect = new ReversIOApi(
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
            null
        );
        $cache = new Cache($reversIoApiConnect);
        // --- Injections croisées ---
//        $cache->setApi($reversIoApiConnect);
        $ordersRetrieveService->setApi($reversIoApiConnect);

        // --- Logic ---
        $buildedChildrens = [];
        $childrens = Category::getChildren(Tools::getValue('categoryId'), $this->context->language->id);

        foreach ($childrens as $children) {
            $buildedChildrens[] = $categoryMapService->getChildrenCategory(
                $children,
                $this->context->language->id,
                $categoryMapRepository->getAllMappedCategories()
            );
        }

        $modelTypesList = $reversIoApiConnect->getModelTypes($this->context->language->iso_code);

        if ($modelTypesList) {
            $modelTypesList = $categoryMapService->formatModelTypes(
                $modelTypesList->getContent()['value']
            );
        }

        $tplVars = [
            'category' => $buildedChildrens,
            'modelTypesList' => $modelTypesList,
            'rootCategory' => $categoryMapService->getRootCategory(
                $this->context->language->id,
                $this->context->shop,
                $categoryMapRepository->getAllMappedCategories()
            ),
            'current' => Tools::getValue('current'),
        ];

        $this->context->smarty->assign($tplVars);

        echo $this->context->smarty->fetch(
            $this->module->getLocalPath().'views/templates/admin/partials/children.tpl'
        );

        die();
    }


    private function updateValues($orderStatus, $orderDateFrom, $orderDateTo)
    {
        Configuration::updateValue(
            Config::ORDERS_STATUS,
            json_encode($orderStatus)
        );

        Configuration::updateValue(
            Config::ORDER_DATE_FROM,
            $orderDateFrom
        );

        Configuration::updateValue(
            Config::ORDER_DATE_TO,
            $orderDateTo
        );
    }
}
