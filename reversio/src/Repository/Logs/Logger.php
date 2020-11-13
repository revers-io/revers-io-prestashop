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

namespace ReversIO\Repository\Logs;

use DateTime;
use Db;
use ReversIO\Repository\BrandRepository;
use ReversIO\Repository\OrderRepository;
use ReversIO\Repository\ProductRepository;

class Logger
{
    /** @var OrderRepository */
    private $orderRepository;

    /** @var ProductRepository */
    private $productRepository;

    /** @var BrandRepository */
    private $brandRepository;

    public function __construct(
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        BrandRepository $brandRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->brandRepository = $brandRepository;
    }

    public function getProductReference($productId)
    {
        return $this->productRepository->getProductReferenceById($productId);
    }

    public function insertOrderLogs($reference, $message)
    {
        $now = new DateTime();
        $orderId = $this->orderRepository->getOrderIdByReference($reference);

        $sql = 'INSERT 
                  INTO ' . _DB_PREFIX_ . 'revers_io_logs (error_log_identifier, type, reference, message, created_date) 
                  VALUES (' . (int)$orderId . ', 
                                "Order", 
                                "' . pSQL($reference) . '", 
                                "' . pSQL($message) . '", 
                                "' . pSQL($now->format("Y-m-d H:i:s")) . '")';

        return Db::getInstance()->execute($sql);
    }

    public function insertProductLogs($productId, $name, $message)
    {
        $now = new DateTime();
        $reference = $this->getProductReference($productId);
        $sql = 'INSERT 
                  INTO ' . _DB_PREFIX_ . 'revers_io_logs 
                            (error_log_identifier, type, name, reference, message, created_date) 
                            VALUES (' . (int)$productId . ', 
                            "Product", "'.pSQL($name).'", 
                            "' . pSQL($reference) . '", 
                            "' . pSQL($message) . '", 
                            "' . pSQL($now->format("Y-m-d H:i:s")) . '")';

        return Db::getInstance()->execute($sql);
    }

    public function insertBrandLogs($name, $message)
    {
        $now = new DateTime();
        $brandId = $this->brandRepository->getBrandIdByName($name);

        $sql = 'INSERT 
                  INTO ' . _DB_PREFIX_ . 'revers_io_logs (error_log_identifier, type, reference, message, created_date) 
                  VALUES (' . (int)$brandId . ', 
                                "Brand", 
                                "' . pSQL($name) . '", 
                                "' . pSQL($message) . '", 
                                "' . pSQL($now->format("Y-m-d H:i:s")) . '")';

        return Db::getInstance()->execute($sql);
    }

    public function deleteLogs($days)
    {
        $now = new DateTime();
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'revers_io_logs 
                WHERE created_date < "'.pSQL($now->format('Y-m-d H:i:s')).'" - INTERVAL ' . (int)$days . ' DAY';
        return Db::getInstance()->execute($sql);
    }

    public function getLogs()
    {
        $query = new \DbQuery();

        $query->select('type, name, reference, message, created_date');
        $query->from('revers_io_logs');

        return Db::getInstance()->executeS($query);
    }
}
