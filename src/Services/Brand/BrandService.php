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

namespace ReversIO\Services\Brand;

use ReversIO;
use ReversIO\Adapter\ArrayAdapter;
use ReversIO\Config\Config;
use ReversIO\Repository\ProductRepository;
use ReversIO\Services\APIConnect\ReversIOApi;
use ReversIO\Services\Cache\Cache;

class BrandService
{
    /**
     * @var ReversIO
     */
    private $module;

    private $arrayAdapter;

    private $listBrands;

    public function __construct(ReversIO $module, ArrayAdapter $arrayAdapter)
    {
        $this->module = $module;
        $this->arrayAdapter = $arrayAdapter;
    }

    public function getBrandIdByManufacturer($manufacturer, $brands)
    {
        if (!$manufacturer) {
            $manufacturer = Config::UNKNOWN_BRAND;
        }

        $formattedBrands = $this->arrayAdapter->formatKeysAndValuesArray($brands, 'name', 'id');

        if (isset($formattedBrands[$manufacturer])) {
            return $formattedBrands[$manufacturer];
        }

        return false;
    }
}
