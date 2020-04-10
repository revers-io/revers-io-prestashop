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

class AdminReversIOSettingsController extends ReversIOAbstractAdminController
{
    /** @var ReversIO */
    public $module;

    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();

        $this->override_folder = 'field-option-settings/';
        $this->tpl_folder = 'field-option-settings/';
    }

    public function init()
    {
        $this->initOptions();

        $orderStatuses = OrderState::getOrderStates($this->context->language->id);
        $orderDateFrom = Configuration::get(Config::ORDER_DATE_FROM);
        $orderDateTo = Configuration::get(Config::ORDER_DATE_TO);

        /** @var \ReversIO\MultiSelect\MultiSelect $reversioMultiSelect */
        $reversioMultiSelect = $this->module->getContainer()->get('reversioMultiSelect');

        $this->context->smarty->assign(
            array(
                'options' => $orderStatuses,
                'ordersStatuses' => $reversioMultiSelect->buildMultiSelectForOrders($orderStatuses),
                'order_date_from' => $orderDateFrom,
                'order_date_to' => $orderDateTo,
            )
        );

        parent::init();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        Media::addJsDef(array(
            'orderImportAjaxUrl' => $this->context->link->getAdminLink(Config::CONTROLLER_ADMIN_AJAX),
        ));

        $this->context->controller->addJqueryPlugin('loading');
        $this->addJS($this->module->getPathUri().'views/js/admin/orders-import.js');
        $this->addJS($this->module->getPathUri().'views/js/admin/disable-log-input.js');
        $this->addJS($this->module->getPathUri().'views/js/admin/orders-date-picker.js');
    }

    protected function initOptions()
    {
        $this->multiple_fieldsets = true;

        $this->fields_options = [
            Config::MAIN_SETTINGS_FIELDS_OPTION_NAME => $this->getMainSettingFields(),
            Config::ORDER_SETTINGS_FIELDS_OPTION_NAME => $this->getOrderSettingsFields(),
//            Config::ORDER_IMPORT_FIELDS_OPTION_NAME => $this->getOrderImportFields(),
            Config::LOGS_SETTINGS_FIELDS_OPTIONS_NAME => $this->getLogsSettingsFields(),
        ];
    }

    private function getMainSettingFields()
    {
        return [
            'title' =>    $this->l('Main settings'),
            'icon' =>     'icon-cogs',
            'description' => $this->l('Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.'),
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
                    'type' => 'text',
                    'class' => 'fixed-width-xxl',
                    'auto_value' => false,
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

    private function getOrderSettingsFields()
    {
        return [
            'title' =>    $this->l('ORDER SETTINGS'),
            'icon' =>     'icon-cogs',
            'description' => $this->l('This settings define the moment when your Customer will be able to proceed to returns. Until then the Returns section on the Order page won\'t be visible. Usually, sellers allow returns once the product was shipped.'),
            'fields' =>    array(
                Config::ORDERS_STATUS => array(
                    'title' => $this->l('Only orders with selected statuses will be allowed for returns'),
                    'type' => 'orders_status',
                ),
                'description' => array(
                    'type' => 'desc',
                    'class' => 'col-lg-12'
                ),
                Config::ORDER_DATE_FROM => array(
                    'title' => $this->l('Synchronise orders with Revers.io between dates'),
                    'type' => 'order_date_from_to',
                ),
                Config::ORDERS_IMPORT_PROGRESS => array(
                    'title' => $this->l('Orders import progress'),
                    'type' => 'progress',
                    'form_group_class' => 'hidden js-revers-io-orders-import',
//                    'class' => 'js-revers-io-orders-import-progress-container',
                ),
            ),

            'buttons' => array(
                array(
                    'title' => $this->l('Save statuses'),
                    'icon' => 'process-icon-save',
                    'name' => 'submitReversIOOrdersStatus',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right'
                ),
                array(
                    'title' => $this->l('Save and Import Orders'),
                    'icon' => 'process-icon-import',
                    'type' => 'button',
                    'class' => 'btn btn-default pull-right js-revers-io-orders-import-button',
                ),
            ),
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
            'buttons' => array(
                array(
                    'title' => $this->l('Save'),
                    'icon' => 'process-icon-save',
                    'name' => 'submitReversIoLogs',
                    'type' => 'submit',
                    'class' => 'btn btn-default pull-right'
                ),
            ),
        ];
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitReversIOOrdersStatus')) {
            Configuration::updateValue(
                Config::ORDERS_STATUS,
                json_encode(Tools::getValue('orders_status'))
            );

            if (!Validate::isDateFormat(Tools::getValue('orders_date_from'))) {
                $this->errors[] = $this->l('Date value is incorrect.');
            } else {
                Configuration::updateValue(
                    Config::ORDER_DATE_FROM,
                    Tools::getValue('orders_date_from')
                );
            }

            if (!Validate::isDateFormat(Tools::getValue('orders_date_to'))) {
                $this->errors[] = $this->l('Date value is incorrect.');
            } else {
                Configuration::updateValue(
                    Config::ORDER_DATE_TO,
                    Tools::getValue('orders_date_to')
                );
            }

            if (empty($this->errors)) {
                $this->confirmations[] = $this->l('Succesfully updated.');
            }

            $this->init();
        }

        if (Tools::isSubmit('submitReversIoLogs')) {
            Configuration::updateValue(
                Config::ENABLE_LOGGING_SETTING,
                Tools::getValue('REVERS_IO_ENABLE_LOGGING_SETTING')
            );
            Configuration::updateValue(
                Config::STORE_LOGS,
                Tools::getValue('REVERS_IO_STORE_LOGS')
            );
            $this->confirmations[] = $this->l('Succesfully updated.');
        }

        $this->processPassword();

        /** @var APIAuthentication $settingAuthentication */
        /** @var  \ReversIO\Services\Decoder\Decoder $decoder */
        $settingAuthentication = $this->module->getContainer()->get('autentification');
        $decoder = $this->module->getContainer()->get('reversio_decoder');

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
            $passwordPlaceholder = $this->getPasswordPlaceholder();

            if (empty(Tools::getValue(Config::SECRET_KEY))
                || Tools::getValue(Config::SECRET_KEY) === $passwordPlaceholder
            ) {
                Configuration::updateValue(
                    Config::TEST_MODE_SETTING,
                    Tools::getValue(Config::TEST_MODE_SETTING)
                );
                Configuration::updateValue(Config::PUBLIC_KEY, Tools::getValue(Config::PUBLIC_KEY));
                $this->confirmations[] = $this->l('The API public key was updated.');
                return;
            }

            Configuration::updateValue(Config::TEST_MODE_SETTING, Tools::getValue(Config::TEST_MODE_SETTING));
            Configuration::updateValue(Config::PUBLIC_KEY, Tools::getValue(Config::PUBLIC_KEY));
            // @codingStandardsIgnoreStart
            Configuration::updateValue(Config::SECRET_KEY, base64_encode(Tools::getValue(Config::SECRET_KEY)));
            // @codingStandardsIgnoreEnd

            $authenticationResponse = $settingAuthentication->authentication(
                Tools::getValue(Config::PUBLIC_KEY),
                $decoder->base64Decoder(Configuration::get(Config::SECRET_KEY))
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

    public function processPassword()
    {
        /**
         * If webservice secret key is already saved, display a placeholder instead of actual password
         */

        if (Tools::strlen(Configuration::get(Config::SECRET_KEY)) > 0) {
            $passwordPlaceholder = $this->getPasswordPlaceholder();
        } else {
            $passwordPlaceholder = '';
        }

        $this->fields_options[Config::MAIN_SETTINGS_FIELDS_OPTION_NAME]['fields'][Config::SECRET_KEY]['value'] =
            $passwordPlaceholder;
    }

    private function getPasswordPlaceholder()
    {
        $placeholder = '';
        for ($i = 0; $i < 10; $i++) {
            $placeholder .= '&#8226;';
        }
        return html_entity_decode($placeholder);
    }
}
