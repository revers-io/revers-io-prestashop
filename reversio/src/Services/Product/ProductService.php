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
use Manufacturer;
use Product;
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
        $brandId,
        $productIdForInsert,
        $language,
        $allMappedCategories,
        $categoriesAndParentsIds,
        $useDefaultDimensions
    ) {
        $product = new Product($productIdForInsert);

        $categoryId = $this->categoryMapService->getModelTypeByCategory(
            (int) $product->id_category_default,
            $allMappedCategories,
            $categoriesAndParentsIds
        );

        $images = $product->getImages($language);

        $imageUrl = "";

        if (!empty($images)) {
            $imageUrl = Context::getContext()->link->getImageLink(
                $product->link_rewrite[$language],
                $images[0]['id_image']
            );
        }

        $weight = (float)$product->weight;
        $length = (int) round($product->depth);
        $width = (int) round($product->width);
        $height = (int) round($product->height);
        if ($useDefaultDimensions === "1") {
            if ($weight <= 0.01) {
                $weight = 0.1;
            }
            if ($length <= 0) {
                $length = 5;
            }
            if ($width <= 0) {
                $width = 5;
            }
            if ($height <= 0) {
                $height = 5;
        	}
        }

        $productInfoArray = [
            "brandId" => $brandId,
            "modelTypeId" => $categoryId,
            "sKU" => $product->reference,
            "label" => $product->name[$language] . " ({$product->reference})",
            "eANs" => [
                $product->ean13,
            ],
            "dimension" => [
                "lengthInCm" => $length,
                "widthInCm" => $width,
                "heightInCm" => $height,
            ],
            "photoUrl" => $imageUrl,
            "additionalInformation" => [
                "isReturnable" => true,
                "isRepairable" => true,
                "isTransportable" => true,
                "isSerializable" => false,
                "isOnSiteInterventionPossible" => false,
                "isCumbersome" => false
            ],
            "state" => 'new',
            "weight" => $weight,
            "isLowValue" => false,
            "id_product" => $product->id,
        ];

        return $productInfoArray;
    }

    public function getInfoAboutProductForUpdate($productIdForUpdate, $modelId, $languageId, $useDefaultDimensions)
    {
        $product = new Product($productIdForUpdate);

        $images = $product->getImages($languageId);

        $imageUrl = "";

        if (!empty($images)) {
            $imageUrl = Context::getContext()->link->getImageLink(
                $product->link_rewrite[$languageId],
                $images[0]['id_image']
            );
        }

        $weight = (float)$product->weight;
        $length = (int) round($product->depth);
        $width = (int) round($product->width);
        $height = (int) round($product->height);
        if ($useDefaultDimensions === "1") {
            if ($weight <= 0.01) {
                $weight = 0.1;
            }
            if ($length <= 0) {
                $length = 5;
            }
            if ($width <= 0) {
                $width = 5;
            }
            if ($height <= 0) {
                $height = 5;
        	}
        }

        $productUpdateInfoArray = [
            "sKU" => $product->reference,
            "eANs" => [
                $product->ean13,
            ],
            "dimension" => [
                "lengthInCm" => $length,
                "widthInCm" => $width,
                "heightInCm" => $height,
            ],

            "photoUrl" => $imageUrl,
            "additionalInformation" => [
                "isReturnable" => true,
                "isRepairable" => true,
                "isTransportable" => true,
                "isSerializable" => false,
                "isOnSiteInterventionPossible" => false,
                "isCumbersome" => false
            ],
            "state" => 'new',
            "weight" => $weight,
            "id_product" => $product->id,
            "modelId" => $modelId,
            'name' => $product->name[$languageId] . "({$product->reference})",
        ];

        return $productUpdateInfoArray;
    }
}
