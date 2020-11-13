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

namespace ReversIO\Repository;

use Db;
use Configuration;
use ReversIO\Config\Config;
use ReversIO\Services\Getters\ColourGetter;

class OrderRepository
{
    /** @var ColourGetter */
    private $colourGetter;

    public function __construct(ColourGetter $colourGetter)
    {
        $this->colourGetter = $colourGetter;
    }

    public function getOrdersForImport($importStatuses, $dateFrom, $dateTo, $limit = false)
    {
        $sql = 'SELECT '._DB_PREFIX_.'orders.`id_order` as `'._DB_PREFIX_.'order`
                FROM '._DB_PREFIX_.'orders 
                LEFT JOIN '._DB_PREFIX_.'revers_io_imported_orders ON 
                            '._DB_PREFIX_.'revers_io_imported_orders.`id_order` = '._DB_PREFIX_.'orders.`id_order`
                WHERE '._DB_PREFIX_.'orders.`current_state`
                 IN ('.implode(',', array_map('intval', $importStatuses)).')
                 AND '._DB_PREFIX_.'revers_io_imported_orders.`id_order` is NULL 
                 AND DATE('._DB_PREFIX_.'orders.date_add) between "'.pSQL($dateFrom).'" AND "'.pSQL($dateTo).'"';

        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        return Db::getInstance()->executeS($sql);
    }

    public function getOrderForImportById($orderId)
    {
        $sql = 'SELECT '._DB_PREFIX_.'orders.id_order as `orderId`
                FROM '._DB_PREFIX_.'orders 
                WHERE '._DB_PREFIX_.'orders.id_order = ' . (int) $orderId;
//                 IN ('.implode(',', array_map('intval', $importStatuses)).')';

        return Db::getInstance()->getValue($sql);
    }

    public function getOrderProductDetails($orderId)
    {
        $query = new \DbQuery();

        $query->select('product_id, product_quantity, total_price_tax_incl');
        $query->from('order_detail');
        $query->where('id_order = '.(int)$orderId);

        return Db::getInstance()->executeS($query);
    }

    public function getOrderIdByReference($reference)
    {
        $query = new \DbQuery();

        $query->select('id_order');
        $query->from('orders');
        $query->where('reference = "' . pSQL($reference) . '"');

        return Db::getInstance()->getValue($query);
    }

    public function getOrdersReference()
    {
        $query = new \DbQuery();

        $query->select('reference');
        $query->from('revers_io_imported_orders');

        return Db::getInstance()->executeS($query);
    }

    public function getStatusValues($languageId)
    {
        $query = new \DbQuery();

        $query->select('id_order_status, name');
        $query->from('revers_io_orders_status_lang');
        $query->where('id_lang = '. (int) $languageId);

        return Db::getInstance()->executeS($query);
    }

    public function getOrderLogDate($orderId)
    {
        $query = new \DbQuery();

        $query->select('created_date');
        $query->from('revers_io_logs');
        $query->where('error_log_identifier = '. (int) $orderId);
        $query->orderBy('created_date DESC');

        return Db::getInstance()->getValue($query);
    }

    public function getOrderUrlById($orderId)
    {
        $query = new \DbQuery();

        $query->select('url');
        $query->from('revers_io_orders_url');
        $query->where('id_order = '. (int) $orderId);

        return Db::getInstance()->getValue($query);
    }

    public function insertSuccessfullyOrNotSuccessfullyImportedOrder($orderReference, $successful)
    {
        $orderId = $this->getOrderIdByReference($orderReference);

        $sql = 'INSERT INTO '._DB_PREFIX_.'revers_io_imported_orders (id_order, reference, successful)
                            VALUES ("'. (int) $orderId.'", "'.pSQL($orderReference).'", '.(int) $successful.')';

        return Db::getInstance()->execute($sql);
    }

    public function deleteUnsuccessfullyOrders()
    {
        $sql = 'DELETE FROM '._DB_PREFIX_.'revers_io_imported_orders WHERE successful = 0';

        return Db::getInstance()->execute($sql);
    }

    public function insertRetrievedOrdersUrl($orderReference, $url)
    {
        $orderId = $this->getOrderIdByReference($orderReference);
        $ordersUrlsIds = $this->getOrderIds();

        if (isset($ordersUrlsIds)) {
            foreach ($ordersUrlsIds as $orderUrlId) {
                if ($orderId === $orderUrlId['id_order']) {
                    return;
                }
            }
        }

        $sql = 'INSERT INTO '._DB_PREFIX_.'revers_io_orders_url (id_order, url)
                            VALUES ('. (int) $orderId.', "'.pSQL($url).'")';

        return Db::getInstance()->execute($sql);
    }

    public function insertOrdersByState($orderReference, $status)
    {
        $colourId = $this->getColourByStatus($status);
        $orderId = $this->getOrderIdByReference($orderReference);
        $currentOrderStatus = $this->getOrderStatus($orderId);

        $ordersFromStatusTable = $this->getOrdersFromReversStatusTable($orderId);

        if ($orderId === $ordersFromStatusTable && (int) $currentOrderStatus !== 1) {
            $sqlDelete = 'DELETE FROM '._DB_PREFIX_.'revers_io_orders WHERE id_order = '.(int)$orderId;
            Db::getInstance()->execute($sqlDelete);

            $sql = 'INSERT INTO '._DB_PREFIX_.'revers_io_orders (id_order, id_order_status)
                            VALUES ('. (int) $orderId.', "'. (int) $colourId.'")';

            return Db::getInstance()->execute($sql);
        } elseif ($orderId !== $ordersFromStatusTable) {
            $sql = 'INSERT INTO '._DB_PREFIX_.'revers_io_orders (id_order, id_order_status)
                            VALUES ('. (int) $orderId.', "'. (int) $colourId.'")';

            return Db::getInstance()->execute($sql);
        }
    }

    public function getOrderStatus($orderId)
    {
        $query = new \DbQuery();

        $query->select('id_order_status');
        $query->from('revers_io_orders');
        $query->where('id_order = '. (int) $orderId);

        return Db::getInstance()->getValue($query);
    }

    private function getOrdersFromReversStatusTable($orderId)
    {
        $query = new \DbQuery();

        $query->select('id_order');
        $query->from('revers_io_orders');
        $query->where('id_order = '. (int) $orderId);

        return Db::getInstance()->getValue($query);
    }

    public function getOrderStateByStateName($name)
    {
        $query = new \DbQuery();

        $query->select('id_order_state');
        $query->from('order_state_lang');
        $query->where('name = "'.pSQL($name).'"');

        return Db::getInstance()->getValue($query);
    }

    public function getOrderReferenceById($orderId)
    {
        $query = new \DbQuery();

        $query->select('reference');
        $query->from('orders');
        $query->where('id_order = "' . (int) $orderId . '"');

        return Db::getInstance()->getValue($query);
    }

    /** This function is returning the orders id's from the revers_io_orders_url table */
    private function getOrderIds()
    {
        $query = new \DbQuery();

        $query->select('id_order');
        $query->from('revers_io_orders_url');

        return Db::getInstance()->executeS($query);
    }

    private function getColourByStatus($status)
    {
        $sql = 'SELECT
                    '._DB_PREFIX_.'revers_io_orders_status.id_order_status
                FROM
                    '._DB_PREFIX_.'revers_io_orders_status
                LEFT JOIN
                    '._DB_PREFIX_.'revers_io_orders_status_lang
                ON
                    '._DB_PREFIX_.'revers_io_orders_status_lang.name = "'.pSQL($status).'"
                WHERE '._DB_PREFIX_.'revers_io_orders_status.id_order_status 
                            = '._DB_PREFIX_.'revers_io_orders_status_lang.id_order_status';

        return Db::getInstance()->getValue($sql);
    }
}
