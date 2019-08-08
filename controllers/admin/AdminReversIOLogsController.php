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

class AdminReversIOLogsController extends ReversIOAbstractAdminController
{
    public $bootstrap = true;

    public function __construct()
    {
        $this->table = 'revers_io_logs';
        $this->identifier = 'reference';

        parent::__construct();

        $this->initList();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->module->getPathUri().'views/css/admin/logs.css');
    }

    private function initList()
    {
        $this->list_no_link = true;

        $this->addRowAction('ViewOrderProductBrand');

        $this->fields_list = array(
            'type' => array(
                'title' => $this->l('Type'),
                'type' => 'select',
                'havingFilter' => true,
                'filter_key' => 'type',
                'list' => array(
                    Config::TYPE_SEARCH_ORDER => $this->l('Order'),
                    Config::TYPE_SEARCH_PRODUCT => $this->l('Product'),
                    Config::TYPE_SEARCH_BRAND => $this->l('Brand'),
                ),
            ),
            'name' => array(
                'title' => $this->l('Name'),
                'type' => 'text',
                'havingFilter' => true
            ),
            'reference' => array(
                'title' => $this->l('Reference'),
                'type' => 'text',
                'havingFilter' => true
            ),
            'message' => array(
                'title' => $this->l('Message'),
                'type' => 'text',
                'havingFilter' => true
            ),
            'created_date' => array(
                'title' => $this->l('Created date'),
                'type' => 'datetime',
                'havingFilter' => true
            )

        );
    }

    public function displayViewOrderProductBrandLink($token, $reference)
    {
        unset($token);

        /** @var \ReversIO\Repository\Logs\LogsRepository $logsRepository */
        $logsRepository = $this->module->getContainer()->get('logsRepository');

        $logsInfo = $logsRepository->getLogIdByReference($reference);

        $viewUrl = $this->context->link->getAdminLink('AdminManufacturers');

        foreach ($logsInfo as $logInfo) {
            if ($logInfo['type'] === Config::TYPE_SEARCH_ORDER) {
                $ordersUrlParam = [
                    'vieworder' => 1,
                    'id_order' => (int)$logInfo['error_log_identifier'],
                ];

                $viewUrl = $this->context->link->getAdminLink('AdminOrders', true, [], $ordersUrlParam);
            } elseif ($logInfo['type'] === Config::TYPE_SEARCH_PRODUCT) {
                $UrlParam = [
                    'updateproduct' => 1,
                    'id_product' => (int)$logInfo['error_log_identifier'],
                ];

                $viewUrl = $this->context->link->getAdminLink('AdminProducts', true, $UrlParam);
            }
        }

        $params = [
            'href' => $viewUrl,
            'action' => $this->l('View'),
            'icon' => 'icon-search-plus',
        ];

        return $this->renderListAction($params);
    }

    private function renderListAction(array $params)
    {
        $this->context->smarty->assign($params);

        return $this->context->smarty->fetch($this->module->getLocalPath().'views/templates/admin/list-action.tpl');
    }
}
