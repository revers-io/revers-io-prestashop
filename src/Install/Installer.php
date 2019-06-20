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

use Language;
use ReversIO\Services\Orders\OrderListBuilder;
use ReversIO\Services\Versions\Versions;
use ReversIOIntegration;
use Tab;

class Installer
{
    /**
     * @var ReversIOIntegration
     */
    private $module;

    /** @var DatabaseInstall */
    private $databaseInstall;

    /** @var OrderListBuilder */
    private $ordersAdminPage;

    /** @var Versions */
    private $version;

    private $moduleConfiguration;

    public function __construct(
        ReversIOIntegration $module,
        DatabaseInstall $databaseInstall,
        OrderListBuilder $ordersAdminPage,
        Versions $version,
        array $moduleConfiguration
    ) {
        $this->module = $module;
        $this->databaseInstall = $databaseInstall;
        $this->ordersAdminPage = $ordersAdminPage;
        $this->version = $version;
        $this->moduleConfiguration = $moduleConfiguration;
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->registerHooks()) {
            return false;
        }

        if (!$this->registerConfiguration()) {
            return false;
        }

        if (!$this->databaseInstall->createDatabaseTables()) {
            return false;
        }

        if (!$this->databaseInstall->insertDefaultOrdersStatus()) {
            return false;
        }

        return true;
    }

    /**
     * Registers Module Hooks.
     *
     * @return bool
     */
    private function registerHooks()
    {
        $hooks = $this->moduleConfiguration['hooks'];

        if (empty($hooks)) {
            return true;
        }

        foreach ($hooks as $hook) {
            if (!$this->module->registerHook($hook)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Registers Module Configuration.
     *
     * @return bool
     */
    private function registerConfiguration()
    {
        $configuration = $this->moduleConfiguration['configuration'];

        if (empty($configuration)) {
            return true;
        }

        foreach ($configuration as $configName => $value) {
            if (!\Configuration::updateValue($configName, $value)) {
                return false;
            }
        }

        return true;
    }
}
