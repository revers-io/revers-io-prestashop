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

namespace ReversIO\Services;

use Category;
use CategoryMap;
use Configuration;
use ReversIO\Repository\CategoryMapRepository;

class CategoryMapService
{
    /** @var CategoryMapRepository */
    private $categoryMapRepository;

    public function __construct(CategoryMapRepository $categoryMapRepository)
    {
        $this->categoryMapRepository = $categoryMapRepository;
    }

    public function getMappedCategoryTree($idLang, $shop, $mappedCategories)
    {
        $rootCategory = Category::getRootCategory($idLang, $shop);
        $categories = Category::getNestedCategories($rootCategory->id, $idLang, true);

        $categoryTree = [];

        foreach ($categories as $category) {
            $categoryTree[] = $this->buildMappedCategoryTree($category, $mappedCategories);
        }

        return $categoryTree;
    }

    public function formatModelTypes($modelTypes)
    {
        $formatedModelTyped = [];

        foreach ($modelTypes as $modelType) {
            $formatedModelTyped[$modelType['id']] = $modelType['label'];
        }

        return $formatedModelTyped;
    }

    public function formatMappedCategoriesFromPost($post)
    {
        $formattedMappedCategories = [];

        foreach ($post as $postItemName => $postItemValue) {
            if (false === explode('-', $postItemName)) {
                continue;
            }

            $explodePostItemName = explode('-', $postItemName);

            $categoryId = (int) $explodePostItemName[0];

            if (!$categoryId) {
                continue;
            }

            $formattedMappedCategories[$categoryId] = $postItemValue;
        }

        return $formattedMappedCategories;
    }

    public function saveMappedCategories($mappedCategories)
    {
        foreach ($mappedCategories as $categoryId => $modelTypeId) {
            if (!$modelTypeId) {
                continue;
            }

            $categoryMap = new CategoryMap();

            $categoryMap->id_category = $categoryId;
            $categoryMap->api_category_id = $modelTypeId;

            if (!$categoryMap->add()) {
                return false;
            }
        }

        return true;
    }

    public function getModelTypeByCategory($categoryId, $allMappedCategories, $categoriesAndParentsIds)
    {
        $modelType = null;

        if (array_key_exists($categoryId, $allMappedCategories)) {
            $modelType = $allMappedCategories[$categoryId];
        } elseif ($categoryId === (int) Configuration::get('PS_HOME_CATEGORY')) {
            return $modelType;
        } else {
            $categoryParentId = $categoriesAndParentsIds[$categoryId];

            $modelType = $this->getModelTypeByCategory(
                $categoryParentId,
                $allMappedCategories,
                $categoriesAndParentsIds
            );
        }

        return $modelType;
    }

    private function buildMappedCategoryTree(array $category, $mappedCategories)
    {
        $categoryTree = [
            'id_category' => $category['id_category'],
            'name' => $category['name'],
            'link_rewrite' => $category['link_rewrite'],
            'modelType' => isset($mappedCategories[$category['id_category']]) ?
                $mappedCategories[$category['id_category']] :
                0
        ];

        if (isset($category['children'])) {
            foreach ($category['children'] as $childCategory) {
                $categoryTree['children'][] = $this->buildMappedCategoryTree($childCategory, $mappedCategories);
            }
        }

        return $categoryTree;
    }
}
