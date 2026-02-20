<?php

namespace ReversIO\Uninstall;

use Configuration;
use ReversIO\Config\Config;
use ReversIO\Install\DatabaseInstall;
use ReversIO;

class Uninstaller
{
    /**
     * @var ReversIO
     */
    private $module;

    /**
     * @var DatabaseInstall
     */
    private $databaseInstall;

    public function __construct(ReversIO $module)
    {
        $this->module = $module;
        $this->databaseInstall = new DatabaseInstall();
    }

    public function init(): bool
    {
        // Suppression des tables
        $this->databaseInstall->dropReversIOTables();

        // Suppression des configurations
        return
            Configuration::deleteByName(Config::PUBLIC_KEY) &&
            Configuration::deleteByName(Config::SECRET_KEY) &&
            Configuration::deleteByName(Config::TEST_MODE_SETTING) &&
            Configuration::deleteByName(Config::ORDERS_STATUS) &&
            Configuration::deleteByName(Config::ENABLE_LOGGING_SETTING) &&
            Configuration::deleteByName(Config::STORE_LOGS) &&
            Configuration::deleteByName(Config::BRAND_INIT_EXPORT) &&
            Configuration::deleteByName(Config::PRODUCT_INIT_EXPORT);
    }
}