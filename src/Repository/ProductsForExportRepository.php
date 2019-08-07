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
use DbQuery;

class ProductsForExportRepository
{
    public function isProductAddedForExport($productId)
    {
        $query = new DbQuery();

        $query->select('id');
        $query->from('revers_io_products_for_export');
        $query->where('id_product = "' . (int) $productId . '"');

        return Db::getInstance()->getValue($query);
    }

    public function deleteFromProductsForExport($productId)
    {
        $sql = 'DELETE FROM '._DB_PREFIX_.'revers_io_products_for_export WHERE id_product = '. (int) $productId;

        return Db::getInstance()->execute($sql);
    }

    public function getProductsForUpdate()
    {
        $sql = 'SELECT id_product 
                  FROM '._DB_PREFIX_.'revers_io_products_for_export 
                  WHERE '._DB_PREFIX_.'revers_io_products_for_export.update = 1';

        return Db::getInstance()->executeS($sql);
    }

    public function getProductForUpdateById($idProduct)
    {
        $sql = 'SELECT id 
                  FROM '._DB_PREFIX_.'revers_io_products_for_export 
                  WHERE id_product = ' . (int) $idProduct . ' 
                  AND ' . _DB_PREFIX_ . 'revers_io_products_for_export.update = 1';

        return Db::getInstance()->getValue($sql);
    }

    public function getProductsForInsert()
    {
        $sql = 'SELECT id_product 
                  FROM '._DB_PREFIX_.'revers_io_products_for_export 
                  WHERE '._DB_PREFIX_.'revers_io_products_for_export.add = 1';

        return Db::getInstance()->executeS($sql);
    }
}
