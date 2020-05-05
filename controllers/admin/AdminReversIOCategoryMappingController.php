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

use ReversIO\Controller\ReversIOAbstractAdminController;

class AdminReversIOCategoryMappingController extends ReversIOAbstractAdminController
{
    public $bootstrap = true;

    public function __construct()
    {
        $this->table = 'revers_io_logs';
        $this->className = 'Product';
        $this->identifier = 'product_id';

        parent::__construct();
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        $this->addCSS($this->module->getLocalPath() . '/views/css/admin/category-mapping.css');
        $this->addJS($this->module->getLocalPath() . '/views/js/admin/category-mapping.js');
    }

    public function initContent()
    {
        $this->displayCategoryMappingWarning();

        parent::initContent();

        $this->initCategoryMappingContent();
    }

    public function displayCategoryMappingWarning()
    {
        $this->informations['revCategoryMap'] = $this->l('You should map as many as possible PrestaShop categories for better experience', self::FILENAME);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitCategoryMapping')) {
            /** @var \ReversIO\Services\CategoryMapService $categoryMapService */
            /** @var \ReversIO\Repository\CategoryMapRepository $categoryMapRepository */
            $categoryMapService = $this->module->getContainer()->get('categoryMapService');
            $categoryMapRepository = $this->module->getContainer()->get('categoryMapRepository');

            $mappedCategoriesFromPost = $categoryMapService->formatMappedCategoriesFromPost($_POST);

            if (empty($mappedCategoriesFromPost)) {
                $this->errors[] = $this->module->l('No category was mapped.');

                return parent::postProcess();
            }

            if (!$categoryMapRepository->deleteAllMappedCategories()) {
                $this->errors[] = $this->module->l('Old mapped categories was not deleted.');

                return parent::postProcess();
            };

            if (!$categoryMapService->saveMappedCategories($mappedCategoriesFromPost)) {
                $this->errors[] = $this->module->l('Failed to map categories');

                return parent::postProcess();
            }

            $this->confirmations[] = $this->module->l('Successfully mapped categories');
        };

        return parent::postProcess();
    }

    private function initCategoryMappingContent()
    {
        /** @var \ReversIO\Services\CategoryMapService $categoryMapService */
        /** @var \ReversIO\Services\APIConnect\ReversIOApi $reversIOAPIConnect */
        /** @var \ReversIO\Repository\CategoryMapRepository $categoryMapRepository */
        $categoryMapService = $this->module->getContainer()->get('categoryMapService');
        $reversIOAPIConnect = $this->module->getContainer()->get('reversIoApiConnect');
        $categoryMapRepository = $this->module->getContainer()->get('categoryMapRepository');

        $categoryTree = $categoryMapService->getMappedCategoryTree(
            $this->context->language->id,
            $this->context->shop,
            $categoryMapRepository->getAllMappedCategories()
        );

        $modelTypesList = $reversIOAPIConnect->getModelTypes($this->context->language->iso_code);

        if ($modelTypesList) {
            $modelTypesList = $categoryMapService->formatModelTypes($modelTypesList->getContent()['value']);
        }

        $tplVars = [
            'categoryTree' => $categoryTree,
            'modelTypesList' => $modelTypesList,
        ];

        $this->context->smarty->assign($tplVars);

        $this->content .= $this->context->smarty->fetch(
            $this->module->getLocalPath().'views/templates/admin/category-mapping-block.tpl'
        );

        $this->context->smarty->assign('content', $this->content);
    }
}
