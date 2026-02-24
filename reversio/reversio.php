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
use ReversIO\Services\Autentification\APIAuthentication;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\DBAL\Query\QueryBuilder;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\BadgeColumn;
use ReversIO\Services\Decoder\Decoder;
use ReversIO\Services\APIConnect\Token;
use ReversIO\Services\Versions\Versions;
use ReversIO\Services\Cache\Cache;
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
use ReversIO\Services\Brand\BrandService;
use ReversIO\Services\Orders\OrdersRetrieveService;
use ReversIO\Services\Orders\OrderStatus;
use ReversIO\Services\Product\ModelService;
use ReversIO\Services\Orders\OrdersRequestBuilder;
use ReversIO\Services\Orders\OrderImportService;
use ReversIO\Services\APIConnect\ReversIOApi;
use ReversIO\Services\APIConnect\ApiClient;
use ReversIO\Services\APIConnect\ApiHeadersBuilder;
use ReversIO\Factory\ClientFactory;
use ReversIO\Proxy\ProxyApiClient;
use ReversIO\Adapter\ArrayAdapter;
use ReversIO\Services\Product\ProductsForExportService;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\Common\HtmlColumn;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ReversIO extends Module
{
    private $moduleContainer;

    public function __construct()
    {
        $this->name = 'reversio';
        $this->version = '1.2.1';
        $this->tab = 'shipping_logistics';
        $this->author = 'Revers.io';
        $this->need_instance = 0;
        $this->description = 'Revers.io';
        $this->module_key = 'c7843c2c00feb49853bd40ff72820396';

        parent::__construct();

        $this->requireAutoloader();
        $this->compile();

        $this->displayName = $this->l('Revers.io');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (Module::isInstalled('reversio')) {
            $isTestModeEnabled = (bool) Configuration::get(ReversIO\Config\Config::TEST_MODE_SETTING);
            if ($isTestModeEnabled) {
                $this->warning = $this->l('Please note: module is in test mode');
            }
        }
    }

    public function install()
    {
        require_once $this->getLocalPath() . 'src/Install/Installer.php';
        require_once $this->getLocalPath() . 'src/Install/DatabaseInstall.php';

        $installer = new \ReversIO\Install\Installer($this);

        return parent::install() &&
            $installer->init() &&
            $this->installTabs();
    }

    public function uninstall()
    {
        require_once $this->getLocalPath() . 'src/Uninstall/Uninstaller.php';
        require_once $this->getLocalPath() . 'src/Install/DatabaseInstall.php';

        $uninstaller = new \ReversIO\Uninstall\Uninstaller($this);

        return parent::uninstall() && $uninstaller->init();
    }


    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(ReversIO\Config\Config::CONTROLLER_CONFIGURATION));
    }

//    public function getContainer(): ContainerInterface
//    {
//        if (null === $this->moduleContainer) {
//            $this->compile(); // construit le conteneur même si le module est en cours d'installation
//        }
//
//        return $this->moduleContainer;
//    }
    protected function getPsService(string $class)
    {
        return SymfonyContainer::getInstance()->get($class);
    }

    /**
     * Return array
     */
    private function installTabs()
    {
        $tabs = $this->getTabs();
        foreach ($tabs as $tabData) {
            $tab = new Tab();
            $tab->class_name = $tabData['class_name'];
            $tab->module = $this->name;
            $tab->id_parent = (int) Tab::getIdFromClassName($tabData['ParentClassName']);

            // Nom multilingue
            $languages = Language::getLanguages(false);
            foreach ($languages as $lang) {
                $tab->name[$lang['id_lang']] = $tabData['name'];
            }

            if (!$tab->add()) {
                return false;
            }
        }
        return true;
    }
    public function getTabs()
    {
        return [
            [
                'name' => 'Revers.io parent controller',
                'ParentClassName' => 'AdminParentModulesSf',
                'class_name' => ReversIO\Config\Config::CONTROLLER_INVISIBLE,
                'visible' => false,
                'parent' => -1,
            ],
            [
                'name' => 'Category mapping',
                'ParentClassName' => ReversIO\Config\Config::CONTROLLER_INVISIBLE,
                'class_name' => ReversIO\Config\Config::CONTROLLER_CATEGORY_MAPPING,
                'module_tab' => true,
                'parent' => ReversIO\Config\Config::CONTROLLER_INVISIBLE,
            ],
            [
                'name' => 'Logs',
                'ParentClassName' => ReversIO\Config\Config::CONTROLLER_INVISIBLE,
                'class_name' => ReversIO\Config\Config::CONTROLLER_LOGS,
                'module_tab' => true,
                'parent' => ReversIO\Config\Config::CONTROLLER_INVISIBLE,
            ],
            [
                'name' => 'Settings',
                'ParentClassName' => ReversIO\Config\Config::CONTROLLER_INVISIBLE,
                'class_name' => ReversIO\Config\Config::CONTROLLER_CONFIGURATION,
                'module_tab' => true,
                'parent' => ReversIO\Config\Config::CONTROLLER_INVISIBLE,
            ],
            [
                'name' => 'Export',
                'ParentClassName' => -1,
                'class_name' => ReversIO\Config\Config::CONTROLLER_EXPORT_LOGS,
                'module_tab' => true,
                'visible' => false,
                'parent' => -1
            ],
            [
                'name' => 'Ajax',
                'ParentClassName' => -1,
                'class_name' => ReversIO\Config\Config::CONTROLLER_ADMIN_AJAX,
                'module_tab' => true,
                'visible' => false,
                'parent' => -1
            ],
        ];
    }
    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
        /** @var \ReversIO\Services\Orders\OrderListBuilder $orderListBuilder */
        $orderListBuilder = $this->getContainer()->get('ordersAdmin');
        $listFields = $orderListBuilder->getFieldList($this->context->language->id);

        /** @var \ReversIO\Repository\OrdersListingRepository $ordersListingRepository */
        $ordersListingRepository = $this->getContainer()->get('ordersListingRepository');

        $params['select'] .= $ordersListingRepository->selectReversValues();
        $params['join'] .= $ordersListingRepository->joinReversTables($this->context->language->id);

        $res = array_slice($params['fields'], 0, 8, true) +
            $listFields +
            array_slice($params['fields'], 3, count($params['fields']) - 1, true) ;

        $params['fields'] = $res;
    }
    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        $definition = $params['definition'];

        $definition->getColumns()->addAfter(
            'osname',
            (new HtmlColumn('reversio_status'))
                ->setName($this->l('Revers.io'))
                ->setOptions([
                    'field' => 'reversio_html',
                ])
        );
    }


    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        $langId = (int) $this->context->language->id;

        foreach (['query_builder', 'search_query_builder'] as $qbKey) {

            if (empty($params[$qbKey])) {
                continue;
            }

            $qb = $params[$qbKey];

            $qb->addSelect("
            CONCAT(
                '<span class=\"badge\" style=\"background-color:', rios.color, ';color:white;\">',
                riosl.name,
                '</span>'
            ) AS reversio_html
        ");

            $qb->addSelect("riosl.name AS reversio_status");

            $qb->leftJoin(
                'o',
                _DB_PREFIX_.'revers_io_orders',
                'rio',
                'rio.id_order = o.id_order'
            );

            $qb->leftJoin(
                'rio',
                _DB_PREFIX_.'revers_io_orders_status',
                'rios',
                'rios.id_order_status = rio.id_order_status'
            );

            $qb->leftJoin(
                'rios',
                _DB_PREFIX_.'revers_io_orders_status_lang',
                'riosl',
                'riosl.id_order_status = rios.id_order_status AND riosl.id_lang = '.$langId
            );
        }
    }

    public function hookActionAdminControllerSetMedia()
    {
        Media::addJsDef(array(
            'initialOrderImportAjaxUrl' => $this->context->link->getAdminLink(
                ReversIO\Config\Config::CONTROLLER_ADMIN_AJAX
            ),
            'token_bo' => Tools::getAdminTokenLite('AdminReversIOAjaxController'),
        ));

        $this->context->controller->addJS($this->getPathUri().'views/js/admin/order-import.js');
    }

    public function hookActionFrontControllerSetMedia()
    {
        Media::addJsDef(array(
            'initialOrderImportAjaxUrl' => $this->context->link->getModuleLink(
                'reversio',
                ReversIO\Config\Config::FO_CONTROLLER
            ),
            'token' => Tools::getToken('token'),
        ));

        $this->context->controller->addJS($this->getPathUri().'views/js/front/order-import-fo.js');
    }

    public function hookDisplayAdminOrder($params)
    {
        try {

            // --- AUTH ---
            $decoder = new \ReversIO\Services\Decoder\Decoder();
            $token = new \ReversIO\Services\APIConnect\Token($decoder);
            $settingAuthentication = new \ReversIO\Services\Autentification\APIAuthentication($token);

            $apiPublicKey = Configuration::get(\ReversIO\Config\Config::PUBLIC_KEY);
            $apiSecretKey = Configuration::get(\ReversIO\Config\Config::SECRET_KEY);

            if ($settingAuthentication->authentication($apiPublicKey, $decoder->base64Decoder($apiSecretKey))) {

                $orderId = (int) $params['id_order'];

                // --- ORDER REPOSITORY ---
                $colourGetter = new \ReversIO\Services\Getters\ColourGetter();
                $orderRepository = new \ReversIO\Repository\OrderRepository($colourGetter);

                $logCreated = $orderRepository->getOrderLogDate($orderId);
                $orderStatus = $orderRepository->getOrderStatus($orderId);

                if ((int) $orderStatus === \ReversIO\Config\Config::CHECK_ERROR_LOG) {

                    $this->context->smarty->assign([
                        'logCreated' => $logCreated,
                        'logLink' => $this->context->link->getAdminLink(\ReversIO\Config\Config::CONTROLLER_LOGS),
                        'orderId' => $orderId,
                    ]);

                    return $this->display(__FILE__, 'views/templates/admin/hook/display-admin-order.tpl');

                } elseif ((int) $orderStatus !== \ReversIO\Config\Config::SUCCESSFULLY_IMPORTED) {

                    $this->context->smarty->assign([
                        'orderId' => $orderId,
                    ]);

                    return $this->display(__FILE__, 'views/templates/admin/hook/display-initial-order-export.tpl');
                }
            }

        } catch (\Exception $e) {
            // silencieux pour ne pas casser l'admin
        }
    }



    public function hookDisplayOrderDetail($params)
    {

        try {

            // --- REPOSITORY ---
            $colourGetter = new \ReversIO\Services\Getters\ColourGetter();
            $orderRepository = new \ReversIO\Repository\OrderRepository($colourGetter);

            // --- ORDER STATUS SERVICE ---
            $orderStatuses = new \ReversIO\Services\Orders\OrderStatus();

            // --- CORE API STACK ---
            $decoder = new \ReversIO\Services\Decoder\Decoder();
            $token = new \ReversIO\Services\APIConnect\Token($decoder);
            $versions = new \ReversIO\Services\Versions\Versions();

            $clientFactory = new \ReversIO\Factory\ClientFactory($versions);
            $apiClient = new \ReversIO\Services\APIConnect\ApiClient($clientFactory);
            $proxyApiClient = new \ReversIO\Proxy\ProxyApiClient($token, $apiClient, $decoder);
            $apiHeadersBuilder = new \ReversIO\Services\APIConnect\ApiHeadersBuilder($token);

            // --- OTHER REPOS ---
            $logsRepository = new \ReversIO\Repository\Logs\LogsRepository();
            $categoryRepository = new \ReversIO\Repository\CategoryRepository();
            $categoryMapRepository = new \ReversIO\Repository\CategoryMapRepository();
            $productsForExportRepository = new \ReversIO\Repository\ProductsForExportRepository();
            $exportedProductsRepository = new \ReversIO\Repository\ExportedProductsRepository();
            $productRepository = new \ReversIO\Repository\ProductRepository();
            $brandRepository = new \ReversIO\Repository\BrandRepository();

            // --- SERVICES ---
            $categoryMapService = new \ReversIO\Services\CategoryMapService($categoryMapRepository);
            $productImporter = new \ReversIO\Services\Product\ProductService($categoryMapService);
            $brandService = new \ReversIO\Services\Brand\BrandService($this, new \ReversIO\Adapter\ArrayAdapter());

            $loggerService = new \ReversIO\Repository\Logs\Logger(
                $orderRepository,
                $productRepository,
                $brandRepository
            );

            // --- ORDERS RETRIEVE SERVICE ---
            $ordersRetrieveService = new \ReversIO\Services\Orders\OrdersRetrieveService();

            // --- API ---
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
                null // cache injecté après
            );

            // --- Cache ---
            $cache = new \ReversIO\Services\Cache\Cache($reversIoApiConnect);
            $reversIoApiConnect->setCache($cache);

            // --- Injection API dans OrdersRetrieveService ---
            $ordersRetrieveService->setApi($reversIoApiConnect);

            // ---------------- LOGIQUE ----------------

            $order = $params['order'];

            $reversIoLink = $orderRepository->getOrderUrlById($order->id);

            if (!in_array($order->current_state, $orderStatuses->getOrderStatusForImport())) {
                return;
            }

            $this->context->smarty->assign([
                'orderId' => $order->id,
            ]);

            $orderData = $ordersRetrieveService->getRetrievedOrder($order->reference);
            if (!$orderData || !$orderData->isSuccess()) {
                return;
            }

            $orderContent = $orderData->getContent();

            $orderReturnInformation = null;
            if (!empty($orderContent['value']['orderLines'])) {
                $orderReturnInformation = $orderContent['value']['orderLines'][0];
            }

            if (empty($orderReturnInformation)) {
                return $this->display(__FILE__, 'views/templates/hook/display-order-initial-export.tpl');
            }

            if (!empty($orderReturnInformation['isOpenForClaims']) && $reversIoLink) {
                $this->context->smarty->assign(['reversIoLink' => $reversIoLink]);
                return $this->display(__FILE__, 'views/templates/hook/display-order-detail.tpl');
            }

            if (isset($orderReturnInformation['isOpenForClaims']) && !$orderReturnInformation['isOpenForClaims']) {
                return $this->display(__FILE__, 'views/templates/hook/display-order-disable-button.tpl');
            }

            if (!empty($orderReturnInformation['hasOpenFile']) && !empty($orderReturnInformation['openFiles']) && $reversIoLink) {
                $this->context->smarty->assign(['reversIoLink' => $reversIoLink]);
                return $this->display(__FILE__, 'views/templates/hook/display-order-return.tpl');
            }

            return $this->display(__FILE__, 'views/templates/hook/display-order-import-failed.tpl');

        } catch (\Throwable $e) {
            PrestaShopLogger::addLog('ReversIO hookDisplayOrderDetail error: '.$e->getMessage(), 3);
            return;
        }
    }




    public function hookActionObjectProductUpdateAfter($params)
    {
        try {

            $productsForExportRepository = new \ReversIO\Repository\ProductsForExportRepository();
            $exportedProductsRepository = new \ReversIO\Repository\ExportedProductsRepository();
            $versions = new \ReversIO\Services\Versions\Versions();

            $productForExportService = new \ReversIO\Services\Product\ProductsForExportService(
                $productsForExportRepository,
                $exportedProductsRepository,
                $versions
            );

            $productForExportService->addProductForExport($params['object']->id);

        } catch (\Exception $e) {
            // ne jamais casser le back-office
        }
    }
    public function hookActionObjectProductAddAfter($params)
    {
        try {

            $productsForExportRepository = new \ReversIO\Repository\ProductsForExportRepository();
            $exportedProductsRepository = new \ReversIO\Repository\ExportedProductsRepository();
            $versions = new \ReversIO\Services\Versions\Versions();

            $productForExportService = new \ReversIO\Services\Product\ProductsForExportService(
                $productsForExportRepository,
                $exportedProductsRepository,
                $versions
            );

            $productForExportService->addProductForExport($params['object']->id);

        } catch (\Exception $e) {
            // ne jamais casser le back-office
        }
    }


    public function hookActionObjectProductDeleteAfter($params)
    {
        try {

            $productsForExportRepository = new \ReversIO\Repository\ProductsForExportRepository();
            $exportedProductsRepository = new \ReversIO\Repository\ExportedProductsRepository();
            $versions = new \ReversIO\Services\Versions\Versions();

            $productForExportService = new \ReversIO\Services\Product\ProductsForExportService(
                $productsForExportRepository,
                $exportedProductsRepository,
                $versions
            );

            $productForExportService->deleteProductFromExport($params['object']->id);

        } catch (\Exception $e) {
            // ne jamais casser le back-office
        }
    }


    public function hookModuleRoutes()
    {
        $tabs = $this->getTabs();
        $controllers = array();

        foreach ($tabs as $tab) {
            $controllers[] = $tab['class_name'];
        }

        if (empty($controllers)) {
            return;
        }

        if (in_array(Tools::getValue('controller'), $controllers)) {
            $this->requireAutoloader();
        }
    }

    public function hookActionOrderStatusUpdate($params)
    {
        $currentStatusName = $params['newOrderStatus']->name;

        try {

            // --- CORE ---
            $decoder = new \ReversIO\Services\Decoder\Decoder();
            $token = new \ReversIO\Services\APIConnect\Token($decoder);
            $versions = new \ReversIO\Services\Versions\Versions();

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
            $brandService = new \ReversIO\Services\Brand\BrandService($this, new \ReversIO\Adapter\ArrayAdapter());

            // OrdersRetrieveService TEMP (injection après)
            $ordersRetrieveService = new \ReversIO\Services\Orders\OrdersRetrieveService(null);

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

            // --- API ---
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
                null // cache injecté après
            );

            // --- CACHE ---
            $cache = new \ReversIO\Services\Cache\Cache($reversIoApiConnect);
            $reversIoApiConnect->setCache($cache);
            // --- INJECTION CROISÉE ---
            $reversIoApiConnect->setOrdersRetrieveService($ordersRetrieveService);
            $ordersRetrieveService->setApi($reversIoApiConnect);

            // --- ORDER STATUS SERVICE ---
            $orderStatuses = new \ReversIO\Services\Orders\OrderStatus();

            // --- MODEL SERVICE ---
            $modelService = new \ReversIO\Services\Product\ModelService(
                $this,
                $orderRepository,
                $productsForExportRepository,
                $reversIoApiConnect,
                $cache,
                $exportedProductsRepository
            );

            // --- ORDER IMPORT ---
            $ordersImport = new \ReversIO\Services\Orders\OrdersRequestBuilder(
                $orderRepository,
                $this,
                $modelService,
                $loggerService
            );

            $orderImportService = new \ReversIO\Services\Orders\OrderImportService(
                $ordersImport,
                $reversIoApiConnect,
                $orderRepository
            );

            // --- LOGIC ---
            $currentStatusId = $orderRepository->getOrderStateByStateName($currentStatusName);
            $statuses = $orderStatuses->getOrderStatusForImport();

            if (in_array($currentStatusId, $statuses)) {

                $orderId = (int)$params['id_order'];

                $response = $orderImportService->importOrder($orderId);

                if ($response && $response->isSuccess()) {

                    $orderReference = $orderRepository->getOrderReferenceById($orderId);
                    $reversIoApiConnect->retrieveOrderUrl($orderReference);

                } else {
                    \PrestaShopLogger::addLog(
                        'ReversIO importOrder failed for order '.$orderId,
                        3
                    );
                }
            }

        } catch (\Throwable $e) {

            \PrestaShopLogger::addLog(
                'ReversIO hookActionOrderStatusUpdate error: '.$e->getMessage(),
                3
            );
        }
    }



    /**
     * Require autoloader
     */
    private function requireAutoloader()
    {
        require_once $this->getLocalPath().'vendor/autoload.php';
    }

    private function compile()
    {
        $containerCache = $this->getLocalPath() . 'var/cache/container.php';
        $containerConfigCache = new \Symfony\Component\Config\ConfigCache(
            $containerCache,
            ReversIO\Config\Config::DISABLE_CACHE
        );
        $containerClass = get_class($this) . 'Container';
        if (!$containerConfigCache->isFresh()) {
            $this->moduleContainer = new \Symfony\Component\DependencyInjection\ContainerBuilder();
            $locator = new \Symfony\Component\Config\FileLocator($this->getLocalPath().'config');
            $loader  = new \Symfony\Component\DependencyInjection\Loader\YamlFileLoader(
                $this->moduleContainer,
                $locator
            );
            $loader->load('config.yml');
            $this->moduleContainer->compile();
            $dumper = new \Symfony\Component\DependencyInjection\Dumper\PhpDumper($this->moduleContainer);
            $containerConfigCache->write(
                $dumper->dump(array('class' => $containerClass)),
                $this->moduleContainer->getResources()
            );
        }
        require_once $containerCache;
        $this->moduleContainer = new $containerClass();
    }
}