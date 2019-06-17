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

namespace ReversIO\Uninstall;

use Configuration;
use ReversIO\Config\Config;
use ReversIO\Install\DatabaseInstall;
use ReversIO\Services\Versions\Versions;
use ReversIOIntegration;
use Tab;

class Uninstaller
{
    /**
     * @var ReversIOIntegration
     */
    private $module;

    /** @var DatabaseInstall */
    private $databaseInstall;

    /** @var Versions */
    private $version;

    /**
     * Uninstall constructor.
     *
     * @param ReversIOIntegration $module
     */
    public function __construct(ReversIOIntegration $module, DatabaseInstall $databaseInstall, Versions $version)
    {
        $this->module = $module;
        $this->databaseInstall = $databaseInstall;
        $this->version = $version;
    }

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->databaseInstall->dropReversIOTables();
        return Configuration::deleteByName(Config::PUBLIC_KEY)
                && Configuration::deleteByName(Config::SECRET_KEY)
                && Configuration::deleteByName(Config::TEST_MODE_SETTING)
                && Configuration::deleteByName(Config::ORDERS_STATUS)
                && Configuration::deleteByName(Config::ENABLE_LOGGING_SETTING)
                && Configuration::deleteByName(Config::STORE_LOGS);
    }
}
