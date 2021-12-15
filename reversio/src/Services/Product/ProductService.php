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

use Configuration;
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

    public function getSkuFromProduct($product, $productDetail) {

        // By default we use product reference
        $sku = "$product->reference";

        // But if not valued correctly, a customer can choose to use the product id
        if (Config::USE_PRODUCTID_AS_SKU) {
            $sku = "$product->id";
        }

        // Or better, use ean13 as the SKU
        if (Config::USE_EAN_AS_SKU) {
            $ean13 = "{$this->getEanFromProduct($product, $productDetail)}";
            if (!empty($ean13))
                $sku = "$ean13";
        }

        return $sku;       
    }
    
    public function getEanFromProduct($product, $productDetail) {
        $ean13OnProduct = "{$product->ean13}";
        $eanOnOrderDetail = "{$productDetail['product_ean13']}";

        $ean13 = $ean13OnProduct;
        if (!empty($eanOnOrderDetail))
            $ean13 = $eanOnOrderDetail;

        return $ean13;
    }

    public function getLabelFromProduct($product, $language, $productDetail) {
        $sku = $this->getSkuFromProduct($product, $productDetail);
        $label = $product->name[$language] . " ({$sku})";
        $productName = $productDetail['product_name'];
        if (!empty($productName)) {
            $label = $productName . " ({$sku})";
        }
        return $label;
    }

    public function getInfoAboutProduct(
        $brandId,
        $productIdForInsert,
        $language,
        $allMappedCategories,
        $categoriesAndParentsIds,
        $productOrderDetail
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
        if (Configuration::get(Config::DEFAULT_DIMENSIONS) === "1") {
            if ($weight <= 0.01) {
                $weight = Config::DEFAULT_DIMENSION_WEIGHT;
            }
            if ($length <= 0) {
                $length = Config::DEFAULT_DIMENSION_LENGTH;
            }
            if ($width <= 0) {
                $width = Config::DEFAULT_DIMENSION_WIDTH;
            }
            if ($height <= 0) {
                $height = Config::DEFAULT_DIMENSION_HEIGHT;
            }
        }

        $sku = $this->getSkuFromProduct($product, $productOrderDetail);
        $label = $this->getLabelFromProduct($product, $language, $productOrderDetail);

        $productInfoArray = [
            "brandId" => $brandId,
            "modelTypeId" => $categoryId,
            "sKU" => $sku,
            "label" => $label,
            "eANs" => [
                $this->getEanFromProduct($product, $productOrderDetail),
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

    public function getInfoAboutProductForUpdate($productIdForUpdate, $modelId, $language, $productOrderDetail)
    {
        $product = new Product($productIdForUpdate);

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
        if (Configuration::get(Config::DEFAULT_DIMENSIONS) === '1') {
            if ($weight <= 0.01) {
                $weight = Config::DEFAULT_DIMENSION_WEIGHT;
            }
            if ($length <= 0) {
                $length = Config::DEFAULT_DIMENSION_LENGTH;
            }
            if ($width <= 0) {
                $width = Config::DEFAULT_DIMENSION_WIDTH;
            }
            if ($height <= 0) {
                $height = Config::DEFAULT_DIMENSION_HEIGHT;
            }
        }

        $sku = $this->getSkuFromProduct($product, $productOrderDetail);
        $label = $this->getLabelFromProduct($product, $language, $productOrderDetail);

        $productUpdateInfoArray = [
            "sKU" => $sku,
            "eANs" => [
                $this->getEanFromProduct($product, $productOrderDetail),
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
            'name' => $label,
        ];

        return $productUpdateInfoArray;
    }
}
