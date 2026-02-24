<?php

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

    public function __construct()
    {
        $this->colourGetter = new ColourGetter();
        $this->nameGetter = new ReversIoSettingNameGetter();
    }

    /**
     * Create database tables
     */
    public function createDatabaseTables()
    {
        $db = Db::getInstance();
        $prefix = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;

        $queries = [];

        $queries[] = "
            CREATE TABLE IF NOT EXISTS `{$prefix}revers_io_logs` (
                `id` int(6) NOT NULL AUTO_INCREMENT,
                `error_log_identifier` int(11),
                `type` VARCHAR(255),
                `name` VARCHAR(255),
                `reference` VARCHAR(255),
                `message` VARCHAR(255),
                `created_date` DATETIME,
                PRIMARY KEY (`id`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8;
        ";

        $queries[] = "
            CREATE TABLE IF NOT EXISTS `{$prefix}revers_io_category_map` (
                `id_category_map` int(6) NOT NULL AUTO_INCREMENT,
                `id_category` int(11),
                `api_category_id` VARCHAR(255),
                PRIMARY KEY (`id_category_map`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8;
        ";

        $queries[] = "
            CREATE TABLE IF NOT EXISTS `{$prefix}revers_io_imported_orders` (
                `id` int(6) NOT NULL AUTO_INCREMENT,
                `id_order` int(11),
                `reference` VARCHAR(255),
                `successful` BOOLEAN,
                PRIMARY KEY (`id`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8;
        ";

        $queries[] = "
            CREATE TABLE IF NOT EXISTS `{$prefix}revers_io_orders_status` (
                `id_order_status` int(6) NOT NULL AUTO_INCREMENT,
                `color` VARCHAR(255),
                PRIMARY KEY (`id_order_status`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8;
        ";

        $queries[] = "
            CREATE TABLE IF NOT EXISTS `{$prefix}revers_io_orders_status_lang` (
                `id_order_status` int(6) NOT NULL,
                `id_lang` int(11) NOT NULL,
                `name` VARCHAR(255),
                PRIMARY KEY (`id_order_status`, `id_lang`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8;
        ";

        $queries[] = "
            CREATE TABLE IF NOT EXISTS `{$prefix}revers_io_orders_url` (
                `id` int(6) NOT NULL AUTO_INCREMENT,
                `id_order` int(11),
                `url` VARCHAR(255),
                PRIMARY KEY (`id`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8;
        ";

        $queries[] = "
            CREATE TABLE IF NOT EXISTS `{$prefix}revers_io_orders` (
                `id_order` int(11) NOT NULL,
                `id_order_status` int(11),
                PRIMARY KEY (`id_order`, `id_order_status`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8;
        ";

        $queries[] = "
            CREATE TABLE IF NOT EXISTS `{$prefix}revers_io_products_for_export` (
                `id` int(6) NOT NULL AUTO_INCREMENT,
                `id_product` int(11) NOT NULL,
                `add` BOOLEAN,
                `update` BOOLEAN,
                PRIMARY KEY (`id`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8;
        ";

        $queries[] = "
            CREATE TABLE IF NOT EXISTS `{$prefix}revers_io_exported_products` (
                `id` int(6) NOT NULL AUTO_INCREMENT,
                `id_product` int(11) NOT NULL,
                `reversio_product_id` VARCHAR(255),
                `add_date` DATETIME,
                `update_date` DATETIME,
                PRIMARY KEY (`id`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8;
        ";

        foreach ($queries as $sql) {
            if (!$db->execute($sql)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Drop all Revers.io tables
     */
    public function dropReversIOTables(): void
    {
        $tables = [
            'revers_io_logs',
            'revers_io_category_map',
            'revers_io_imported_orders',
            'revers_io_orders_status',
            'revers_io_orders_status_lang',
            'revers_io_orders',
            'revers_io_orders_url',
            'revers_io_products_for_export',
            'revers_io_exported_products'
        ];

        foreach ($tables as $table) {
            Db::getInstance()->execute('DROP TABLE IF EXISTS '._DB_PREFIX_.$table);
        }
    }

    /**
     * Insert default order statuses
     */
    public function insertDefaultOrdersStatus(): bool
    {
        $db = Db::getInstance();
        $colours = $this->colourGetter->getColour();
        $names = $this->nameGetter->getName();
        $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        // Insert colours and retrieve ID for each status
        foreach ($colours as $index => $colour) {
            $db->execute(
                'INSERT INTO '._DB_PREFIX_.'revers_io_orders_status (color)
                 VALUES ("'.pSQL($colour).'")'
            );
            $id_order_status = (int) $db->Insert_ID();

            // Insert corresponding language entry
            $name = isset($names[$index]) ? $names[$index] : 'Status '.$id_order_status;
            $db->execute(
                'INSERT INTO '._DB_PREFIX_.'revers_io_orders_status_lang (id_order_status, id_lang, name)
                 VALUES ('.$id_order_status.', '.$id_lang.', "'.pSQL($name).'")'
            );
        }

        return true;
    }
}