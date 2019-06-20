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

use ReversIO\Controller\ReversIOAbstractAdminController;
use ReversIO\Config\Config;
use ReversIO\Repository\TabRepository;
use ReversIO\Services\Autentification\APIAuthentication;

class AdminReversIOIntegrationSettingsController extends ReversIOAbstractAdminController
{
    /** @var ReversIOIntegration */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();
    }

    public function init()
    {
        $this->initOptions();
        parent::init();
    }

    protected function initOptions()
    {
        $orders = $this->getOrders();
        $this->multiple_fieldsets = true;

        $this->fields_options = [
            Config::MAIN_SETTINGS_FIELDS_OPTION_NAME => $this->getMainSettingFields(),
            Config::ORDER_SETTINGS_FIELDS_OPTION_NAME => $this->getOrderSettingsFields($orders),
            Config::LOGS_SETTINGS_FIELDS_OPTIONS_NAME => $this->getLogsSettingsFields(),
            Config::CRON_SETTINGS_FIELDS_OPTIONS_NAME => $this->getCronSettingsFields(),
        ];
    }

    private function getMainSettingFields()
    {
        return [
            'title' =>    $this->l('Main settings'),
            'icon' =>     'icon-cogs',
            'fields' =>    array(
                Config::TEST_MODE_SETTING => array(
                    'title' => $this->l('Revers.io test mode'),
                    'class' => 'fixed-width-lg',
                    'type' => 'bool'
                ),
                Config::PUBLIC_KEY => array(
                    'title' => $this->l('API public key'),
                    'class' => 'fixed-width-xxl',
                    'type' => 'text'
                ),
                Config::SECRET_KEY => array(
                    'title' => $this->l('API secret key'),
                    'class' => 'fixed-width-xxl',
                    'type' => 'text'
                ),
            ),
            'buttons' => array(
                array(
                    'title' => $this->l('Save'),
                    'icon' => 'process-icon-save',
                    'name' => 'submitReversIOAuthentication',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right'
                ),
            ),
        ];
    }

    private function getOrderSettingsFields($orders)
    {
        return [
            'title' =>    $this->l('ORDER SETTINGS'),
            'icon' =>     'icon-cogs',
            'fields' =>    array(
                Config::ORDERS_STATUS => array(
                    'title' => $this->l('Send orders to revers.io when status is set to'),
                    'type' => 'select',
                    'identifier' => 'order',
                    'class' => 'fixed-width-lg',
                    'list' => $orders,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        ];
    }

    private function getLogsSettingsFields()
    {
        return [
            'title' =>    $this->l('LOGS'),
            'icon' =>     'icon-cogs',
            'fields' =>    array(
                Config::ENABLE_LOGGING_SETTING => array(
                    'title' => $this->l('Enable logging'),
                    'type' => 'bool'
                ),
                Config::STORE_LOGS => array(
                    'title' => $this->l('Store logs for'),
                    'type' => 'text',
                    'class' => 'fixed-width-lg',
                    'cast' => 'intval',
                    'suffix' => 'days',
                    'desc' => $this->l('Input 0 to not store logs'),
                ),
                'REVERSIODownload' => array(
                    'title' => '',
                    'type' => '',
                    'desc' =>
                        '<a href="'.$this->context->link->getAdminLink(Config::CONTROLLER_EXPORT_LOGS).'">'
                                    .$this->l('Click here to download logs').'</a>',
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            )
        ];
    }

    protected function getCronSettingsFields()
    {
        $pathForProducts = $this->module->getLocalPath() . 'reversiointegration.products.import.cron.php';
        $pathForOrders = $this->module->getLocalPath() . 'reversiointegration.orders.import.cron.php';

        return [
            'title' =>    $this->l('Cron jobs'),
            'icon' =>     'icon-cogs',
            'description' => $this->l('Please note that you have to set cronjob (automatic product and order import) 
                                by youself and we recommend to do it every hour.
                                In order to set the cronjob, you need to write the command: ').'<br>'.
                            $this->l('php').$pathForProducts.'<br>'.
                            $this->l('php').$pathForOrders.'<br>'.
                            $this->l('into the terminal with crontab'),
        ];
    }

    private function getOrders()
    {
        $choices = [];

        foreach (OrderState::getOrderStates($this->context->language->id) as $order) {
            $choices[] = [
                'name' => $order['name'], 'order' => $order['id_order_state'],
            ];
        }

        return $choices;
    }

    public function postProcess()
    {
        /** @var APIAuthentication $settingAuthentication */
        $settingAuthentication = $this->module->getContainer()->get('autentification');

        /** @var TabRepository $tab */
        $tab = $this->module->getContainer()->get('tabRepository');

        $parentTabId = $tab->getInvisibleTabId();

        /** Delete logs when the controller is loaded */
        if (Configuration::get(Config::STORE_LOGS) !== "0"
            && Configuration::get(Config::ENABLE_LOGGING_SETTING) !== "0") {
            /** @var \ReversIO\Repository\Logs\Logger $loggerService */
            $loggerService = $this->module->getContainer()->get('loggerService');
            $loggerService->deleteLogs(Configuration::get(Config::STORE_LOGS));
        }

        if (Tools::isSubmit('submitReversIOAuthentication') &&
            (Tools::isSubmit(Config::PUBLIC_KEY) && Tools::isSubmit(Config::SECRET_KEY))
        ) {
            Configuration::updateValue(Config::TEST_MODE_SETTING, Tools::getValue(Config::TEST_MODE_SETTING));
            Configuration::updateValue(Config::PUBLIC_KEY, Tools::getValue(Config::PUBLIC_KEY));
            Configuration::updateValue(Config::SECRET_KEY, Tools::getValue(Config::SECRET_KEY));

            $authenticationResponse = $settingAuthentication->authentication(
                Tools::getValue(Config::PUBLIC_KEY),
                Tools::getValue(Config::SECRET_KEY)
            );

            if ($authenticationResponse) {
                $this->showHideModuleTabs(1, $parentTabId);
                $this->confirmations[] = $this->l('Successfully connected to Revers.io API.');
            }

            if (!$authenticationResponse) {
                $this->errors[] =
                    $this->module->l('Authorisation failed. Please check your API keys.', self::FILENAME);
                $this->showHideModuleTabs(0, -1);
            }

            $this->displayTestModeWarning();
        }

        parent::postProcess();
    }
}
