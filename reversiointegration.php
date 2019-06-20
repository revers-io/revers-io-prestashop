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

if (!defined('_PS_VERSION_')) {
    exit;
}

class ReversIOIntegration extends Module
{
    private $moduleContainer;

    public function __construct()
    {
        $this->name = $this->l('reversiointegration');
        $this->version = '1.0.0';
        $this->tab = 'others';
        $this->author = 'Invertus';
        $this->need_instance = 0;
        $this->description = 'Revers.io integration';

        parent::__construct();

        $this->requireAutoloader();
        $this->compile();

        $this->displayName = $this->l('Revers.io integration');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        $this->confirmUninstall = $this->l('Ar you sure you want to uninstall?');
    }

    public function install()
    {
        /** @var \ReversIO\Install\Installer $installer */
        $installer = $this->getContainer()->get('installer');

        return parent::install() && $installer->init();
    }

    public function uninstall()
    {
        /** @var \ReversIO\Uninstall\Uninstaller $uninstaller */
        $uninstaller = $this->getContainer()->get('uninstaller');
        return parent::uninstall() && $uninstaller->init();
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink(ReversIO\Config\Config::CONTROLLER_CONFIGURATION));
    }

    public function getContainer()
    {
        return $this->moduleContainer;
    }

    /**
     * Return array
     */
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
        ];
    }

    public function importOrders()
    {
        /** @var \ReversIO\Services\APIConnect\ReversIOApi $reversIOAPIConnect */
        $reversIOAPIConnect = $this->getContainer()->get('reversIoApiConnect');
        return $reversIOAPIConnect->importOrders();
    }

    public function insertOrdersUrl()
    {
        /** @var \ReversIO\Services\APIConnect\ReversIOApi $reversIOAPIConnect */
        $reversIOAPIConnect = $this->getContainer()->get('reversIoApiConnect');

        return $reversIOAPIConnect->retrieveOrderUrl();
    }

    public function insertOrUpdateProducts()
    {
        /** @var \ReversIO\Services\APIConnect\ReversIOApi $reversIOAPIConnect */
        $reversIOAPIConnect = $this->getContainer()->get('reversIoApiConnect');
        /** @var \ReversIO\Repository\ProductsForExportRepository $productsExport */
        $productsExport = $this->getContainer()->get('productExportRepository');

        if (Configuration::get(ReversIO\Config\Config::PRODUCT_INIT_EXPORT) === "1") {
            $products = Product::getProducts($this->context->language->id, 0, 0, 'id_product', 'ASC');

            $productIdsArray = $this->formatProducts($products);

//            TODO: why to create error catch logic if never use?
            $reversIOAPIConnect->putProducts($productIdsArray, $this->context->language->id);

            Configuration::updateValue(ReversIO\Config\Config::PRODUCT_INIT_EXPORT, 0);
            return;
        }

        $productForInsert = $productsExport->getProductsForInsert();
        $productForUpdate = $productsExport->getProductsForUpdate();

        if (!empty($productForInsert)) {
            $reversIOAPIConnect->putProducts($productForInsert, $this->context->language->id);
        }

        if (!empty($productForUpdate)) {
            $reversIOAPIConnect->updateProducts($productForUpdate, $this->context->language->id);
        }
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

    public function hookDisplayAdminOrder($params)
    {
        $orderId = $params['id_order'];

        /** @var \ReversIO\Repository\OrderRepository $orderRepository */
        $orderRepository = $this->getContainer()->get('orderRepository');
        $logCreated = $orderRepository->getOrderLogDate($orderId);

        $orderStatus = $orderRepository->getOrderStatus($orderId);

        if ((int) $orderStatus === ReversIO\Config\Config::CHECK_ERROR_LOG) {
            $this->context->smarty->assign(array(
                'logCreated' => $logCreated,
                'logLink' => $this->context->link->getAdminLink(ReversIO\Config\Config::CONTROLLER_LOGS),
            ));

            return $this->display(__FILE__, 'views/templates/admin/hook/displayAdminOrder.tpl');
        }
    }

    public function hookDisplayOrderDetail($params)
    {
        /** @var \ReversIO\Repository\OrderRepository $orderRepository */
        $orderRepository = $this->getContainer()->get('orderRepository');
        $reversIoLink = $orderRepository->getOrderUrlById($params['order']->id);

        if ($reversIoLink) {
            $this->context->smarty->assign(array(
                'reversIoLink' => $reversIoLink,
            ));

            return $this->display(__FILE__, 'views/templates/hook/displayOrderDetail.tpl');
        }
    }

    public function hookActionObjectProductUpdateAfter($params)
    {
        /** @var \ReversIO\Services\Product\ProductsForExportService $productForExportService */
        $productForExportService = $this->getContainer()->get('productForExportService');
        $productForExportService->addProductForExport($params['object']->id);
    }

    public function hookActionObjectProductAddAfter($params)
    {
        /** @var \ReversIO\Services\Product\ProductsForExportService $productForExportService */
        $productForExportService = $this->getContainer()->get('productForExportService');
        $productForExportService->addProductForExport($params['object']->id);
    }

    public function hookActionObjectProductDeleteAfter($params)
    {
        /** @var \ReversIO\Services\Product\ProductsForExportService $productForExportService */
        $productForExportService = $this->getContainer()->get('productForExportService');
        $productForExportService->deleteProductFromExport($params['object']->id);
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

    private function formatProducts($products)
    {
        $productIdsArray = [];

        foreach ($products as $product) {
            $productIdsArray[] = [
                'id_product' => $product['id_product']
            ];
        }

        return $productIdsArray;
    }
}
