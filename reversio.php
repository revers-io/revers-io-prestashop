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

if (!defined('_PS_VERSION_')) {
    exit;
}

class ReversIO extends Module
{
    private $moduleContainer;

    public function __construct()
    {
        $this->name = $this->l('reversio');
        $this->version = '1.0.0';
        $this->tab = 'shipping_logistics';
        $this->author = 'Invertus';
        $this->need_instance = 0;
        $this->description = 'Revers.io';

        parent::__construct();

        $this->requireAutoloader();
        $this->compile();

        $this->displayName = $this->l('Revers.io');
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        $this->confirmUninstall = $this->l('Ar you sure you want to uninstall?');

        if (Module::isInstalled('reversio')) {
            $isTestModeEnabled = (bool) Configuration::get(ReversIO\Config\Config::TEST_MODE_SETTING);
            if ($isTestModeEnabled) {
                $this->warning = $this->l('Please note: module is in test mode');
            }
        }
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
        /** @var APIAuthentication $settingAuthentication */
        /** @var ReversIO\Services\Decoder\Decoder $decoder */
        $settingAuthentication = $this->getContainer()->get('autentification');
        $decoder = $this->getContainer()->get('reversio_decoder');

        $apiPublicKey = Configuration::get(Config::PUBLIC_KEY);
        $apiSecretKey = Configuration::get(Config::SECRET_KEY);

        if ($settingAuthentication->authentication($apiPublicKey, $decoder->base64Decoder($apiSecretKey))) {
            $orderId = $params['id_order'];

            /** @var \ReversIO\Repository\OrderRepository $orderRepository */
            $orderRepository = $this->getContainer()->get('orderRepository');
            $logCreated = $orderRepository->getOrderLogDate($orderId);

            $orderStatus = $orderRepository->getOrderStatus($orderId);

            if ((int) $orderStatus === ReversIO\Config\Config::CHECK_ERROR_LOG) {
                $this->context->smarty->assign(array(
                    'logCreated' => $logCreated,
                    'logLink' => $this->context->link->getAdminLink(ReversIO\Config\Config::CONTROLLER_LOGS),
                    'orderId' => $orderId,
                ));

                return $this->display(__FILE__, 'views/templates/admin/hook/display-admin-order.tpl');
            } elseif ((int) $orderStatus !== Config::SUCCESSFULLY_IMPORTED) {
                $this->context->smarty->assign(array(
                    'orderId' => $orderId,
                ));
                return $this->display(__FILE__, 'views/templates/admin/hook/display-initial-order-export.tpl');
            }
        }
    }

    public function hookDisplayOrderDetail($params)
    {
        /** @var \ReversIO\Repository\OrderRepository $orderRepository */
        /** @var \ReversIO\Services\Orders\OrderStatus $orderStatuses */
        /** @var \ReversIO\Services\Orders\OrdersRetrieveService $orderRetrieveService */
        $orderRepository = $this->getContainer()->get('orderRepository');
        $orderStatuses = $this->getContainer()->get('orderStatuses');
        $reversIoLink = $orderRepository->getOrderUrlById($params['order']->id);
        $orderRetrieveService = $this->getContainer()->get('ordersRetrieveService');

        if (in_array($params['order']->current_state, $orderStatuses->getOrderStatusForImport())) {
            $this->context->smarty->assign(array(
                'orderId' => $params['order']->id,
            ));

            $orderReturnInformation =
                $orderRetrieveService->getRetrievedOrder($params['order']->reference)['orderLines'][0];

            if (empty($orderReturnInformation)) {
                return $this->display(__FILE__, 'views/templates/hook/display-order-initial-export.tpl');
            }

            if ($orderReturnInformation['isOpenForClaims'] && $reversIoLink) {
                $this->context->smarty->assign(array(
                    'reversIoLink' => $reversIoLink,
                ));

                return $this->display(__FILE__, 'views/templates/hook/display-order-detail.tpl');
            }

            if (!$orderReturnInformation['isOpenForClaims']) {
                return $this->display(__FILE__, 'views/templates/hook/display-order-disable-button.tpl');
            }

            if ($orderReturnInformation['hasOpenFile'] &&
                !empty($orderReturnInformation['openFiles']) && $reversIoLink
            ) {
                $this->context->smarty->assign(array(
                    'reversIoLink' => $reversIoLink,
                ));

                return $this->display(__FILE__, 'views/templates/hook/display-order-return.tpl');
            }

            return $this->display(__FILE__, 'views/templates/hook/display-order-import-failed.tpl');
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

    public function hookActionOrderStatusUpdate($params)
    {
        $currentStatusName = $params['newOrderStatus']->name;
        /** @var \ReversIO\Repository\OrderRepository $orderRepository */
        /** @var  \ReversIO\Services\Orders\OrderStatus $orderStatuses */
        /** @var \ReversIO\Services\Orders\OrderImportService $orderImportService */
        /** @var \ReversIO\Services\APIConnect\ReversIOApi $reversIoApiConnect */
        $orderRepository = $this->getContainer()->get('orderRepository');
        $orderStatuses = $this->getContainer()->get('orderStatuses');
        $orderImportService = $this->getContainer()->get('orderImportService');
        $reversIoApiConnect = $this->getContainer()->get('reversIoApiConnect');

        $currentStatusId = $orderRepository->getOrderStateByStateName($currentStatusName);
        $statuses = $orderStatuses->getOrderStatusForImport();

        if (in_array($currentStatusId, $statuses)) {
            try {
                $response = $orderImportService->importOrder($params['id_order']);
                if ($response->isSuccess()) {
                    $orderReference = $orderRepository->getOrderReferenceById($params['id_order']);
                    $reversIoApiConnect->retrieveOrderUrl($orderReference);
                }
            } catch (Exception $e) {
                $this->context->controller->errors[] = $this->l('Order was not imported');
            }
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
