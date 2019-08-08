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

class CategoryMapRepository
{
    public function getAllMappedCategories()
    {
        $query = new DbQuery();

        $query->select('api_category_id, id_category');
        $query->from('revers_io_category_map');

        $db = Db::getInstance();

        $resource = $db->query($query);
        $result = array();

        while ($row = $db->nextRow($resource)) {
            $result[$row['id_category']] = $row['api_category_id'];
        }

        return $result;
    }

    public function deleteAllMappedCategories()
    {
        $sql = 'DELETE FROM '._DB_PREFIX_.'revers_io_category_map';

        return Db::getInstance()->execute($sql);
    }

    public function getCountCategoryMap()
    {
        $sql = 'SELECT id_category_map FROM '._DB_PREFIX_.'revers_io_category_map';

        return Db::getInstance()->executeS($sql);
    }
}
