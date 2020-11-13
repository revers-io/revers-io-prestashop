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

class AdminReversIOAjaxController extends ReversIOAbstractAdminController
{
    public function ajaxProcessImportOrdersToReversIo()
    {
        if (Tools::getValue('token_bo') !==  Tools::getAdminTokenLite('AdminReversIOAjaxController')) {
            die();
        }

        $this->updateValues(
            Tools::getValue('orders_status'),
            Tools::getValue('orders_date_from'),
            Tools::getValue('orders_date_to')
        );

        /** @var \ReversIO\Services\Orders\OrderImportService $orderImportService */
        /** @var \ReversIO\Repository\OrderRepository $orderRepository */
        $orderImportService = $this->module->getContainer()->get('orderImportService');
        $orderRepository = $this->module->getContainer()->get('orderRepository');

        $orderRepository->deleteUnsuccessfullyOrders();

        $sumFailed = 0;
        $sumImported = 0;

        $reversIoOrderImportResponse = $orderImportService->importOrders();

        $sumFailed = $sumFailed + $reversIoOrderImportResponse->getTotalFailed();
        $sumImported = $sumImported + $reversIoOrderImportResponse->getTotalImported();

        
        while (!$reversIoOrderImportResponse->getImportFinished()) {
            $reversIoOrderImportResponse = $orderImportService->importOrders();
            $sumFailed = $sumFailed + $reversIoOrderImportResponse->getTotalFailed();
            $sumImported = $sumImported + $reversIoOrderImportResponse->getTotalImported();

            /** Enables to break at some point if too many errors */
            if ($sumFailed > 500) {
                break;
            }
        }

        $jsonData = [
            'totalImported' => $sumImported,
            'totalFailed' => $sumFailed,
            'importFinished' => $reversIoOrderImportResponse->getImportFinished(),
            'totalSum' => $sumFailed+$sumImported,
        ];

        $this->ajaxRender(json_encode($jsonData));
    }

    public function ajaxProcessImportOrderToReversIo()
    {
        if (Tools::getValue('token_bo') !==  Tools::getAdminTokenLite('AdminReversIOAjaxController')) {
            die();
        }

        /** @var \ReversIO\Services\Orders\OrderImportService $orderImportService */
        /** @var \ReversIO\Repository\OrderRepository $orderRepository */
        /** @var \ReversIO\Services\APIConnect\ReversIOApi $reversIoApiConnect */
        $orderImportService = $this->module->getContainer()->get('orderImportService');
        $orderRepository = $this->module->getContainer()->get('orderRepository');
        $reversIoApiConnect = $this->module->getContainer()->get('reversIoApiConnect');

        $orderRepository->deleteUnsuccessfullyOrders();
        $orderId = Tools::getValue('orderId');
        $orderReference = $orderRepository->getOrderReferenceById($orderId);

        try {
            $reversIoOrderImportResponse = $orderImportService->importOrder($orderId);
            if ($reversIoOrderImportResponse->isSuccess()) {
                $reversIoApiConnect->retrieveOrderUrl($orderReference);
            }
            $this->ajaxRender(json_encode($reversIoOrderImportResponse));
        } catch (Exception $e) {
            $this->ajaxRender(json_encode(['success' => false]));
        }
    }

    public function ajaxProcessDisplayCategories()
    {
        /** @var \ReversIO\Services\CategoryMapService $categoryMapService */
        /** @var \ReversIO\Services\APIConnect\ReversIOApi $reversIOAPIConnect */
        /** @var \ReversIO\Repository\CategoryMapRepository $categoryMapRepository */
        $categoryMapService = $this->module->getContainer()->get('categoryMapService');
        $reversIOAPIConnect = $this->module->getContainer()->get('reversIoApiConnect');
        $categoryMapRepository = $this->module->getContainer()->get('categoryMapRepository');

        $buildedChildrens = [];
        $childrens = Category::getChildren(Tools::getValue('categoryId'), $this->context->language->id);
        foreach ($childrens as $children) {
            $buildedChildrens[] = $categoryMapService->getChildrenCategory($children, $this->context->language->id, $categoryMapRepository->getAllMappedCategories());
        }

        $modelTypesList = $reversIOAPIConnect->getModelTypes($this->context->language->iso_code);

        if ($modelTypesList) {
            $modelTypesList = $categoryMapService->formatModelTypes($modelTypesList->getContent()['value']);
        }

        $tplVars = [
            'category' => $buildedChildrens,
            'modelTypesList' => $modelTypesList,
            'rootCategory' =>  $categoryMapService->getRootCategory($this->context->language->id, $this->context->shop, $categoryMapRepository->getAllMappedCategories()),
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
