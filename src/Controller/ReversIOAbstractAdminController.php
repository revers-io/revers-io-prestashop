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

namespace ReversIO\Controller;

use Configuration;
use ModuleAdminController;
use ReversIO\Config\Config;
use ReversIO\Services\Autentification\APIAuthentication;
use ReversIOIntegration;
use Tab;
use Tools;

/**
 * Class ReversIOAbstractAdminController
 */
class ReversIOAbstractAdminController extends ModuleAdminController
{
    const FILENAME = 'ReversIOAbstractAdminController';

    /**
     * @var ReversIOIntegration
     */
    public $module;

    public function init()
    {
        $this->displayTestModeWarning();

        /** @var APIAuthentication $settingAuthentication */
        $settingAuthentication = $this->module->getContainer()->get('autentification');

        $apiPublicKey = Configuration::get(Config::PUBLIC_KEY);
        $apiSecretKey = Configuration::get(Config::SECRET_KEY);

        if (!$settingAuthentication->authentication($apiPublicKey, $apiSecretKey)) {
            $this->showHideModuleTabs(0, -1);
            $this->redirectToSettings();
        }

        parent::init();
    }

    /**
     * Display test mode warning if test mode is enabled
     */
    public function displayTestModeWarning()
    {
        $isTestModeEnabled = (bool) Configuration::get(Config::TEST_MODE_SETTING);

        if ($isTestModeEnabled) {
            $this->warnings['revTestMode'] = $this->module->l('Please note: module is in test mode', self::FILENAME);
        } elseif (isset($this->warnings['revTestMode'])) {
            unset($this->warnings['revTestMode']);
        }
    }

    private function redirectToSettings()
    {
        if ($this instanceof \AdminReversIOIntegrationSettingsController) {
            return;
        }

        Tools::redirectAdmin($this->context->link->getAdminLink(Config::CONTROLLER_CONFIGURATION));
    }

    public function showHideModuleTabs($tabStatus, $parent)
    {
        $moduleTabs = $this->module->getTabs();

        foreach ($moduleTabs as $moduleTab) {

            if ($moduleTab['class_name'] === Config::CONTROLLER_INVISIBLE
                || $moduleTab['class_name'] === Config::CONTROLLER_EXPORT_LOGS) {
                continue;
            }

            if ($moduleTab['class_name'] !== Config::CONTROLLER_CONFIGURATION) {
                $tabInstance = Tab::getInstanceFromClassName($moduleTab['class_name']);
                $tabInstance->active = $tabStatus;

                if($this->module->isVersion173()) {
                    $tabInstance->id_parent = $parent;
                }

                $tabInstance->update();
            }
        }
    }
}
