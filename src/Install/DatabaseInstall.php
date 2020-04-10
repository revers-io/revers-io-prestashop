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

namespace ReversIO\Install;

use Configuration;
use Db;
use ReversIO\Services\Getters\ColourGetter;
use ReversIO\Services\Getters\ReversIoSettingNameGetter;

class DatabaseInstall
{
    /** @var ColourGetter */
    private $colourGetter;

    /** @var ReversIoSettingNameGetter */
    private $nameGetter;

    public function __construct(ColourGetter $colourGetter, ReversIoSettingNameGetter $nameGetter)
    {
        $this->colourGetter = $colourGetter;
        $this->nameGetter = $nameGetter;
    }

    public function createDatabaseTables()
    {
        return Db::getInstance()->execute('
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'revers_io_logs` (
			`id` int(6) NOT NULL AUTO_INCREMENT,
			`error_log_identifier` int(11),
			`type` VARCHAR(255),
			`name` VARCHAR(255),
			`reference` VARCHAR(255),
			`message` VARCHAR(255),
			`created_date` DATETIME,
			PRIMARY KEY(`id`)
		) ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8;
		
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'revers_io_category_map` (
			`id_category_map` int(6) NOT NULL AUTO_INCREMENT UNIQUE,
			`id_category` int(11),
			`api_category_id` VARCHAR(255),
			PRIMARY KEY(`id_category`)
		) ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8;
		
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'revers_io_imported_orders` (
			`id` int(6) NOT NULL AUTO_INCREMENT,
			`id_order` VARCHAR(255),
			`reference` VARCHAR(255),
			`successful` BOOLEAN,
			PRIMARY KEY(`id`)
		) ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8;
		
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'revers_io_orders_status` (
			`id_order_status` int(6) NOT NULL AUTO_INCREMENT,
			`color` VARCHAR(255),
			PRIMARY KEY(`id_order_status`)
		) ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8;
		
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'revers_io_orders_status_lang` (
			`id_order_status` int(6) NOT NULL AUTO_INCREMENT,
			`id_lang` int(11),
			`name` VARCHAR(255),
			PRIMARY KEY(`id_order_status`, `id_lang`)
		) ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8;
		
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'revers_io_orders_url` (
			`id` int(6) NOT NULL AUTO_INCREMENT,
			`id_order` int(11),
			`url` VARCHAR(255),
			PRIMARY KEY(`id`)
		) ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8;
		
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'revers_io_orders` (
			`id` int(6) NOT NULL AUTO_INCREMENT,
			`id_order` int(11),
			`id_order_status` int(11),
			PRIMARY KEY(`id`)
		) ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8;
		
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'revers_io_products_for_export` (
			`id` int(6) NOT NULL AUTO_INCREMENT UNIQUE,
			`id_product` int(11) NOT NULL ,
			`add` BOOLEAN,
			`update` BOOLEAN,
			PRIMARY KEY(`id_product`)
		) ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8;
		
		CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'revers_io_exported_products` (
			`id` int(6) NOT NULL AUTO_INCREMENT UNIQUE,
			`id_product` int(11) NOT NULL ,
			`reversio_product_id` VARCHAR(255),
			`add_date` DATETIME,
			`update_date` DATETIME,
			PRIMARY KEY(`id_product`)
		) ENGINE='._MYSQL_ENGINE_.' default CHARSET=utf8;');
    }

    public function dropReversIOTables()
    {
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'revers_io_logs');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'revers_io_category_map');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'revers_io_imported_orders');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'revers_io_orders_status');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'revers_io_orders_status_lang');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'revers_io_orders');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'revers_io_orders_url');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'revers_io_products_for_export');
        Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.'revers_io_exported_products');
    }

    public function insertDefaultOrdersStatus()
    {
        $colours = $this->colourGetter->getColour();
        $names = $this->nameGetter->getName();

        foreach ($colours as $colour) {
            $sql = 'INSERT INTO '._DB_PREFIX_.'revers_io_orders_status (color)
                            VALUES ("'.pSQL($colour).'")';

            Db::getInstance()->execute($sql);
        }

        foreach ($names as $name) {
            $sqlLang = 'INSERT INTO '._DB_PREFIX_.'revers_io_orders_status_lang (id_lang, name)
                            VALUES ('.(int)Configuration::get('PS_LANG_DEFAULT').', "'.pSQL($name).'")';

            Db::getInstance()->execute($sqlLang);
        }
        return true;
    }
}
