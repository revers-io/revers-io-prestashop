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

use Product;
use ProductForExport;
use ReversIO\Repository\ExportedProductsRepository;
use ReversIO\Repository\ProductsForExportRepository;
use ReversIO\Services\Versions\Versions;

class ProductsForExportService
{
    private $productsForExportRepository;

    private $exportedProductsRepository;

    private $version;

    public function __construct(
        ProductsForExportRepository $productsForExportRepository,
        ExportedProductsRepository $exportedProductsRepository,
        Versions $version
    ) {
        $this->productsForExportRepository = $productsForExportRepository;
        $this->exportedProductsRepository = $exportedProductsRepository;
        $this->version = $version;
    }

    /**
     * Adds product for export add if product is not already imported and not included for export
     *
     * @param $idProduct
     */
    public function addProductForExport($idProduct)
    {
        $product = new Product($idProduct);

        if (!$product->active) {
            return;
        }

        if (!$product->state) {
            return;
        }

        $isProductAddedForExport = $this->productsForExportRepository->isProductAddedForExport($idProduct);

        if ($isProductAddedForExport) {
            return;
        }

        $isProductExported = $this->exportedProductsRepository->isProductExported($idProduct);

        $exportAdd = false;
        $exportUpdate = false;

        if ($isProductExported) {
            $exportUpdate = true;
        } else {
            $exportAdd = true;
        }

        $productForExport = new ProductForExport();
        $productForExport->id_product = $idProduct;
        $productForExport->add = $exportAdd;
        $productForExport->update = $exportUpdate;

        $productForExport->save();
    }

    /**
     * Deletes product that are included for import
     *
     * @param $idProduct
     */
    public function deleteProductFromExport($idProduct)
    {
        return $this->productsForExportRepository->deleteFromProductsForExport($idProduct);
    }
}
