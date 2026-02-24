<?php

namespace ReversIO\Install;

use Configuration;
use DateTime;
use ReversIO;
use ReversIO\Config\Config;

class Installer
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
        return
            $this->registerHooks() &&
            $this->registerConfiguration() &&
            $this->databaseInstall->createDatabaseTables() &&
            $this->databaseInstall->insertDefaultOrdersStatus();
    }

    /**
     * Register module hooks
     */
    private function registerHooks(): bool
    {
        $hooks = [
            'actionAdminOrdersListingFieldsModifier',
            'displayAdminOrder',
            'displayOrderDetail',
            'actionObjectProductUpdateAfter',
            'actionObjectProductDeleteAfter',
            'actionObjectProductAddAfter',
            'actionAdminControllerSetMedia',
            'actionFrontControllerSetMedia',
            'actionOrderGridDefinitionModifier',
            'actionOrderGridQueryBuilderModifier',
            'actionOrderStatusUpdate',
            'moduleRoutes',
        ];

        foreach ($hooks as $hook) {
            if (!$this->module->registerHook($hook)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Register module configuration
     */
    private function registerConfiguration(): bool
    {
        $defaults = [
            Config::TEST_MODE_SETTING       => false,
            Config::ENABLE_LOGGING_SETTING => false,
            Config::STORE_LOGS              => false,
            Config::BRAND_INIT_EXPORT       => false,
            Config::PRODUCT_INIT_EXPORT     => false,
        ];

        foreach ($defaults as $key => $value) {
            if (!Configuration::updateValue($key, $value)) {
                return false;
            }
        }

        $now = new DateTime();

        Configuration::updateValue(
            Config::ORDER_DATE_FROM,
            date('Y-m-d', strtotime($now->format('Y-m-d') . ' -15 days'))
        );

        Configuration::updateValue(
            Config::ORDER_DATE_TO,
            $now->format('Y-m-d')
        );

        Configuration::updateValue(
            Config::ORDERS_STATUS,
            json_encode([4])
        );

        return true;
    }
}