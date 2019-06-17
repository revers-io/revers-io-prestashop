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

namespace ReversIO\Services\Product;

use Context;
use ReversIO\Config\Config;
use ReversIO\Services\CategoryMapService;

class ProductService
{
    /** @var CategoryMapService */
    private $categoryMapService;
    public function __construct(CategoryMapService $categoryMapService)
    {
        $this->categoryMapService = $categoryMapService;
    }

    public function getInfoAboutProduct(
        $brandContent,
        $productsForInsert,
        $language,
        $allMappedCategories,
        $categoriesAndParentsIds
    ) {
        $productsArray = [];

        foreach ($productsForInsert as $productForInsert) {
            $product = new \Product($productForInsert['id_product']);

            $manufacture = new \Manufacturer($product->id_manufacturer);

            $brandName = $manufacture->name;

            if ($brandName === null) {
                $brandName = Config::UNKNOWN_BRAND;
            }

            $brandId = null;

            foreach ($brandContent['value'] as $brand) {
                if ($brand['name'] === $brandName) {
                    $brandId = $brand['id'];

                    break;
                }
            }

            $categoryId = $this->categoryMapService->getModelTypeByCategory(
                (int) $product->id_category_default,
                $allMappedCategories,
                $categoriesAndParentsIds
            );

            $images = $product->getImages($language);

            if(!empty($images)) {
                $imageUrl = Context::getContext()->link->getImageLink(
                    $product->link_rewrite[$language],
                    $images[0]['id_image']
                );
            }

            $productsArray[] = [
                "brandId" => $brandId,
                "modelTypeId" => $categoryId,
                "sKU" => $product->reference,
                "label" => $product->name[$language],
                "eANs" => [
                    $product->ean13,
                ],
                "dimension" => [
                    "lengthInCm" => (float)$product->depth,
                    "widthInCm" => (float)$product->width,
                    "heightInCm" => (float)$product->height,
                ],
                "photoUrl" => $imageUrl,
                "additionalInformation" => [
                    "isReturnable" => true,
                    "isRepairable" => true,
                    "isTransportable" => true,
                    "isSerializable" => true,
                    "isOnSiteInterventionPossible" => true,
                    "isCumbersome" => true
                ],
                "state" => 'new',
                "weight" => (float)$product->weight,
                "isLowValue" => true,
                "id_product" => $product->id,
            ];
        }

        return $productsArray;
    }

    public function getInfoAboutProductForUpdate($modelsContent, $productsForUpdate, $language)
    {
        $productsArray = [];

        foreach ($productsForUpdate as $productForUpdate) {
            $product = new \Product($productForUpdate['id_product']);

            foreach ($modelsContent['value'] as $modelContent) {
                if ($modelContent['name'] === $product->name[$language]) {
                    $productsArray[] = [
                        "sKU" => $product->reference,
                        "eANs" => [
                            $product->ean13,
                        ],
                        "dimension" => [
                            "lengthInCm" => (float)$product->depth,
                            "widthInCm" => (float)$product->width,
                            "heightInCm" => (float)$product->height,
                        ],

                        "photoUrl" => $product->getLink(),
                        "additionalInformation" => [
                            "isReturnable" => true,
                            "isRepairable" => true,
                            "isTransportable" => true,
                            "isSerializable" => true,
                            "isOnSiteInterventionPossible" => true,
                            "isCumbersome" => true
                        ],
                        "state" => 'new',
                        "weight" => (float)$product->weight,
                        "id_product" => $product->id,
                        "modelId" => $modelContent['id'],
                        'name' => $product->name[$language],
                    ];
                }
            }
        }

        return $productsArray;
    }
}
