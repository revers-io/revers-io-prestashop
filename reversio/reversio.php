<?php

require_once __DIR__ . '/vendor/autoload.php';

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
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ReversIO extends Module
{
    private $moduleContainer;

    public $tabs = [
        [
            'name' => 'Revers.io',
            'parent_class_name' => 'AdminParentModulesSf',
            'class_name' => Config::CONTROLLER_INVISIBLE,
            'visible' => true,
            'parent' => -1,
        ],
        [
            'name' => 'Category mapping',
            'parent_class_name' => Config::CONTROLLER_INVISIBLE,
            'class_name' => Config::CONTROLLER_CATEGORY_MAPPING,
            'module_tab' => true,
            'parent' => Config::CONTROLLER_INVISIBLE,
        ],
        [
            'name' => 'Logs',
            'parent_class_name' => Config::CONTROLLER_INVISIBLE,
            'class_name' => Config::CONTROLLER_LOGS,
            'module_tab' => true,
            'parent' => Config::CONTROLLER_INVISIBLE,
        ],
        [
            'name' => 'Settings',
            'parent_class_name' => Config::CONTROLLER_INVISIBLE,
            'class_name' => Config::CONTROLLER_CONFIGURATION,
            'module_tab' => true,
            'parent' => Config::CONTROLLER_INVISIBLE,
        ],
        [
            'name' => 'Export',
            'parent_class_name' => -1,
            'class_name' => Config::CONTROLLER_EXPORT_LOGS,
            'module_tab' => true,
            'visible' => false,
            'parent' => -1
        ],
        [
            'name' => 'Ajax',
            'parent_class_name' => -1,
            'class_name' => Config::CONTROLLER_ADMIN_AJAX,
            'module_tab' => true,
            'visible' => false,
            'parent' => -1
        ],
    ];

    protected function _installTabs()
    {
        foreach ($this->tabs as $tabData) {
            $tab = new Tab();
            $tab->class_name = $tabData['class_name'];
            $tab->module = $this->name;

            // Gestion du parent
            if (isset($tabData['parent_class_name']) && $tabData['parent_class_name'] !== -1) {
                $id_parent = (int) Tab::getIdFromClassName($tabData['parent_class_name']);
                if ($id_parent === 0) {
                    // Si le parent n'existe pas, on met à la racine "DEFAULT"
                    $id_parent = (int) Tab::getIdFromClassName('DEFAULT');
                }
                $tab->id_parent = $id_parent;
            } else {
                // Par défaut, on met à la racine "DEFAULT"
                $tab->id_parent = (int) Tab::getIdFromClassName('DEFAULT');
            }

            // Visibilité
            $tab->visible = $tabData['visible'] ?? true;

            // Icone par défaut (tu peux personnaliser)
            $tab->icon = $tabData['icon'] ?? 'settings_applications';

            // Traduction du nom
            $languages = [Language::getLanguages(true, (int)Configuration::get('PS_LANG_DEFAULT'))[0]];
            foreach ($languages as $lang) {
                $tab->name[$lang['id_lang']] = $this->l($tabData['name']);
            }

            // Enregistrement
            try {
                $tab->save();
            } catch (Exception $e) {
                error_log('Error installing tab: ' . $e->getMessage());
                return false;
            }
        }

        return true;
    }   

    public function __construct()
    {
        $this->name = $this->l('reversio');
        $this->version = '1.3.0';
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
            $isTestModeEnabled = (bool) Configuration::get(ReversIO\Config\Config::TEST_MODE_SETTING,null,ShopConstraint::allShops());
            if ($isTestModeEnabled) {
                $this->warning = $this->l('Please note: module is in test mode');
            }
        }
    }

    public function install()
    {
        /** @var \ReversIO\Install\Installer $installer */
        $installer = $this->getContainer()->get('installer');

        if (!parent::install()) {
            return false;
        }

        if (!$this->_installTabs()) {
            return false;
        }

        if (!$installer->init()) {
            return false;
        }

        return true;
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

    public function getContainer() : ContainerInterface
    {
        return $this->moduleContainer;
    }

    public function hookActionAdminOrdersListingFieldsModifier($params)
    {
        /** @var \ReversIO\Services\Orders\OrderListBuilder $orderListBuilder */
        $orderListBuilder = $this->getContainer()->get('ordersAdmin');
        $listFields = $orderListBuilder->getFieldList($this->context->language->id);

        /** @var \ReversIO\Repository\OrdersListingRepository $ordersListingRepository */
        $ordersListingRepository = $this->getContainer()->get('ordersListingRepository');

        if (isset($params['select'])) {
            $params['select'] .= $ordersListingRepository->selectReversValues();
        }

        if (isset($params['join'])) {
            $params['join'] .= $ordersListingRepository->joinReversTables($this->context->language->id);
        }

        $res = array_slice($params['fields'], 0, 8, true) +
            $listFields +
            array_slice($params['fields'], 3, count($params['fields']) - 1, true) ;

        $params['fields'] = $res;
    }

    public function hookActionAdminControllerSetMedia()
    {
        Media::addJsDef(array(
            'initialOrderImportAjaxUrl' => $this->context->link->getAdminLink(
               Config::CONTROLLER_ADMIN_AJAX
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
               Config::FO_CONTROLLER
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

        $apiPublicKey = Configuration::get(Config::PUBLIC_KEY,null,ShopConstraint::allShops());
        $apiSecretKey = Configuration::get(Config::SECRET_KEY,null,ShopConstraint::allShops());

        if ($settingAuthentication->authentication($apiPublicKey, $decoder->base64Decoder($apiSecretKey))) {
            $orderId = $params['id_order'];

            /** @var \ReversIO\Repository\OrderRepository $orderRepository */
            $orderRepository = $this->getContainer()->get('orderRepository');
            $logCreated = $orderRepository->getOrderLogDate($orderId);

            $orderStatus = $orderRepository->getOrderStatus($orderId);

            if ((int) $orderStatus ===Config::CHECK_ERROR_LOG) {
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

            if ($orderReturnInformation['hasOpenFile'] &&
                !empty($orderReturnInformation['openFiles']) && $reversIoLink
            ) {
                $this->context->smarty->assign(array(
                    'reversIoLink' => $reversIoLink,
                ));

                return $this->display(__FILE__, 'views/templates/hook/display-order-return.tpl');
            }

            if (!$orderReturnInformation['isOpenForClaims']) {
                $this->context->smarty->assign(array(
                    'reversIoLink' => $reversIoLink,
                ));
                
                return $this->display(__FILE__, 'views/templates/hook/display-order-disable-button.tpl');
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
           Config::DISABLE_CACHE
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
