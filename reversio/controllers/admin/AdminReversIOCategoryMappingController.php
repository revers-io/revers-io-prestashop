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
use ReversIO\Services\APIConnect\ApiHeadersBuilder;
use ReversIO\Services\APIConnect\ApiClient;
use ReversIO\Factory\ClientFactory;
use ReversIO\Proxy\ProxyApiClient;
use ReversIO\Services\Cache\Cache;
use ReversIO\Services\Versions\Versions as ReversioVersions;
use ReversIO\Repository\OrderRepository;
use ReversIO\Repository\ProductRepository;
use ReversIO\Repository\CategoryRepository;
use ReversIO\Repository\ExportedProductsRepository;
use ReversIO\Repository\ProductsForExportRepository;
use ReversIO\Repository\Logs\LogsRepository;
use ReversIO\Services\Orders\OrdersRetrieveService;
use ReversIO\Services\Product\ProductService;
use ReversIO\Services\Brand\BrandService;
use ReversIO\Services\Product\ModelService;
use ReversIO\Services\Getters\ColourGetter;
use ReversIO\Services\Getters\ReversIoSettingNameGetter;
use ReversIO\Repository\BrandRepository;




class AdminReversIOCategoryMappingController extends ReversIOAbstractAdminController
{
    public $bootstrap = true;

    public function __construct()
    {
        $this->table = 'revers_io_logs';
        $this->className = 'Product';
        $this->identifier = 'product_id';

        parent::__construct();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        Media::addJsDef(array(
            'categoryDisplayAjax' => $this->context->link->getAdminLink(Config::CONTROLLER_ADMIN_AJAX),
        ));

        $this->addCSS($this->module->getLocalPath() . '/views/css/admin/category-mapping.css');
        $this->addJS($this->module->getLocalPath() . '/views/js/admin/category-map.js');
    }

    public function initContent()
    {
        $this->displayCategoryMappingWarning();

        parent::initContent();

        $this->initCategoryMappingContent();
    }

    public function displayCategoryMappingWarning()
    {
        $this->informations['revCategoryMap'] = $this->l('You should map as many as possible PrestaShop categories for better experience', self::FILENAME);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitCategoryMapping')) {
            /** @var \ReversIO\Services\CategoryMapService $categoryMapService */
            /** @var \ReversIO\Repository\CategoryMapRepository $categoryMapRepository */
            $categoryMapRepository = new CategoryMapRepository();
            $categoryMapService = new CategoryMapService($categoryMapRepository);

            $mappedCategoriesFromPost = $categoryMapService->formatMappedCategoriesFromPost($_POST);

            if (empty($mappedCategoriesFromPost)) {
                $this->errors[] = $this->module->l('No category was mapped.');

                return parent::postProcess();
            }

            if (!$categoryMapRepository->deleteAllMappedCategories()) {
                $this->errors[] = $this->module->l('Old mapped categories was not deleted.');

                return parent::postProcess();
            };

            if (!$categoryMapService->saveMappedCategories($mappedCategoriesFromPost)) {
                $this->errors[] = $this->module->l('Failed to map categories');

                return parent::postProcess();
            }

            $this->confirmations[] = $this->module->l('Successfully mapped categories');
        };

        return parent::postProcess();
    }

    private function initCategoryMappingContent()
    {
        // --- Repositories ---
        $categoryMapRepository = new CategoryMapRepository();
        $categoryRepository = new CategoryRepository();
        $productsForExportRepository = new ProductsForExportRepository();
        $exportedProductsRepository = new ExportedProductsRepository();
        $productRepository = new ProductRepository();
        $brandRepository = new BrandRepository();
        $logsRepository = new LogsRepository();

        // --- Helpers ---
        $decoder = new Decoder();
        $token = new Token($decoder);
        $versions = new ReversioVersions();

        $clientFactory = new ClientFactory($versions);
        $apiClient = new ApiClient($clientFactory);
        $proxyApiClient = new ProxyApiClient($token, $apiClient, $decoder);
        $apiHeadersBuilder = new ApiHeadersBuilder($token);

        // --- Order repo ---
        $colourGetter = new ColourGetter();
        $orderRepository = new OrderRepository($colourGetter);

        // --- Services ---
        $categoryMapService = new CategoryMapService($categoryMapRepository);
        $brandService = new BrandService($this->module, new \ReversIO\Adapter\ArrayAdapter());
        $productImporter = new ProductService($categoryMapService);

        $loggerService = new \ReversIO\Repository\Logs\Logger(
            $orderRepository,
            $productRepository,
            $brandRepository
        );

        // --- Cache (module) ---



        // --- OrdersRetrieveService sans API pour l’instant ---
        $ordersRetrieveService = new OrdersRetrieveService();


        // --- API ---
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
        $reversIoApiConnect->setOrdersRetrieveService($ordersRetrieveService);
        $reversIoApiConnect->setCache($cache);
        $ordersRetrieveService->setApi($reversIoApiConnect);

        // --- Model service ---
        $modelService = new ModelService(
            $this->module,
            $orderRepository,
            $productsForExportRepository,
            $reversIoApiConnect,
            $cache,
            $exportedProductsRepository
        );

        // --- Category data ---
        $rootCategory = $categoryMapService->getRootCategory(
            $this->context->language->id,
            $this->context->shop,
            $categoryMapRepository->getAllMappedCategories()
        );

        $categoryTree = $categoryMapService->getMappedCategoryTree(
            $this->context->language->id,
            $this->context->shop,
            $categoryMapRepository->getAllMappedCategories()
        );

        $modelTypesList = $reversIoApiConnect->getModelTypes($this->context->language->iso_code);

        if ($modelTypesList) {
            $modelTypesList = $categoryMapService->formatModelTypes($modelTypesList->getContent()['value']);
        }

        $tplVars = [
            'categoryTree' => $categoryTree,
            'modelTypesList' => $modelTypesList,
            'rootCategory' => $rootCategory,
            'key' => 0,
        ];

        $this->context->smarty->assign($tplVars);

        $this->content .= $this->context->smarty->fetch(
            $this->module->getLocalPath().'views/templates/admin/category-mapping-block.tpl'
        );

        $this->context->smarty->assign('content', $this->content);
    }


}
