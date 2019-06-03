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

class OrdersListingRepository
{
    public function selectReversValues()
    {
        return ', '._DB_PREFIX_.'revers_io_orders_status_lang.name AS reversioname,
                                '._DB_PREFIX_.'revers_io_orders_status.color AS revcolour, 
                                '._DB_PREFIX_.'revers_io_orders_status.id_order_status';
    }

    public function joinReversTables($languageId)
    {
        return ' LEFT JOIN '._DB_PREFIX_.'revers_io_orders ON a.id_order = '._DB_PREFIX_.'revers_io_orders.id_order
                 LEFT JOIN '._DB_PREFIX_.'revers_io_orders_status 
                 ON '._DB_PREFIX_.'revers_io_orders_status.id_order_status = 
                                    '._DB_PREFIX_.'revers_io_orders.id_order_status
                 LEFT JOIN '._DB_PREFIX_.'revers_io_orders_status_lang ON (
                                    '._DB_PREFIX_.'revers_io_orders_status.id_order_status = 
                                    '._DB_PREFIX_.'revers_io_orders_status_lang.id_order_status AND 
                                    '._DB_PREFIX_.'revers_io_orders_status_lang.`id_lang` = '. (int) $languageId.')';
    }
}
