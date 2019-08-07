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

namespace ReversIO\Config;

/**
 * Class Config
 *
 * @package ReversIO\Services\Config
 */
class Config
{
    const API_URL_BASE_DEMO = 'https://demo-customer-api.revers.io/api/v1/';

    const API_URL_BASE_LIVE = 'https://customer-api.revers.io/api/v1/';

    const DISABLE_CACHE = false;

    const CONTROLLER_INVISIBLE = 'AdminReversIOTabs';

    /**
     * Module settings page controller
     */
    const CONTROLLER_CONFIGURATION = 'AdminReversIOSettings';

    /**
     * Module info page controller
     */
    const CONTROLLER_LOGS = 'AdminReversIOLogs';

    /**
     * Module category mapping page controller
     */
    const CONTROLLER_CATEGORY_MAPPING = 'AdminReversIOCategoryMapping';

    /**
     * Logs export page controller
     */
    const CONTROLLER_EXPORT_LOGS = 'AdminReversIOExport';

    const CONTROLLER_ADMIN_AJAX = 'AdminReversIOAjax';

    const FO_CONTROLLER = 'Ajax';

    const TEST_MODE_SETTING = 'REVERS_IO_TEST_MODE_SETTING';

    const PUBLIC_KEY = 'REVERS_IO_API_PUBLIC_KEY';

    const SECRET_KEY = 'REVERS_IO_API_SECRET_KEY';

    const ORDERS_STATUS = 'REVERS_IO_ORDER_STATUS';

    const ORDER_DATE_FROM = 'REVERS_IO_ORDER_DATE_FROM';

    const ORDER_DATE_TO = 'REVERS_IO_ORDER_DATE_TO';

    const ORDERS_IMPORT_PROGRESS = 'REVERS_IO_ORDER_IMPORT_PROGRESS';

    const ENABLE_LOGGING_SETTING = 'REVERS_IO_ENABLE_LOGGING_SETTING';

    const STORE_LOGS = 'REVERS_IO_STORE_LOGS';

    const MAIN_SETTINGS_FIELDS_OPTION_NAME = 'home';

    const ORDER_SETTINGS_FIELDS_OPTION_NAME = 'ordersettings';

    const ORDER_IMPORT_FIELDS_OPTION_NAME = 'orderimport';

    const LOGS_SETTINGS_FIELDS_OPTIONS_NAME = 'logs';

    const CRON_SETTINGS_FIELDS_OPTIONS_NAME = 'cron';

    const TYPE_SEARCH_ORDER = 'Order';

    const TYPE_SEARCH_PRODUCT = 'Product';

    const TYPE_SEARCH_BRAND = 'Brand';

    const SYNCHRONIZED_SUCCESSFULLY_ORDERS_STATUS = 'Synchronized with Revers.io';

    const SYNCHRONIZED_UNSUCCESSFULLY_ORDERS_STATUS = 'Check error log';

    const PRODUCT_INIT_EXPORT = 'REVERS_IO_PRODUCT_INIT_EXPORT';

    const BRAND_INIT_EXPORT = 'REVERS_IO_BRAND_INIT_EXPORT';

    const CURRENCY_EUR = "EUR";

    const CURRENCY_GBP = "GBP";

    const CHECK_ERROR_LOG = 2;

    const SUCCESSFULLY_IMPORTED = 1;

    const UNKNOWN_BRAND = 'Unknown Brand';
}
