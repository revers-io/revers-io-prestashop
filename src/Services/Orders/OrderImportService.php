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

namespace ReversIO\Services\Orders;

use ReversIO\Repository\OrderRepository;
use ReversIO\Response\ReversIoOrderImportResponse;
use ReversIO\Services\APIConnect\ReversIOApi;

class OrderImportService
{
    /** @var OrdersRequestBuilder */
    private $ordersImportRequestService;

    /** @var ReversIOApi */
    private $reversIoApiConnect;

    /** @var OrderRepository */
    private $orderRepository;

    /**
     * One session import size
     */
    private $defaultBatchSize = 2;

    public function __construct(
        OrdersRequestBuilder $ordersImportRequestService,
        ReversIOApi $reversIoApiConnect,
        OrderRepository $orderRepository
    ) {
        $this->ordersImportRequestService = $ordersImportRequestService;
        $this->reversIoApiConnect = $reversIoApiConnect;
        $this->orderRepository = $orderRepository;
    }

    /**
     * This function is importing the orders into the Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/ImportOrder?
     */
    public function importOrders()
    {
        $reversIoOrderImportResponse = new ReversIoOrderImportResponse();

        $orders = $this->ordersImportRequestService->getOrdersForImports($this->defaultBatchSize);

        if (empty($orders)) {
            $reversIoOrderImportResponse->setImportFinished(true);
            return $reversIoOrderImportResponse;
        }

        foreach ($orders as $order) {
            try {
                $orderImportData = $this->ordersImportRequestService->getOrderImportData($order['id_order']);

                $importOrderResponse = $this->reversIoApiConnect->importOrderRequest($orderImportData);

                if ($importOrderResponse->isSuccess()) {
                    $orderReference = $this->orderRepository->getOrderReferenceById($order['id_order']);
                    $this->reversIoApiConnect->retrieveOrderUrl($orderReference);
                    $reversIoOrderImportResponse->increaseTotalImported();
                } else {
                    $reversIoOrderImportResponse->increaseTotalFailed();
                }
            } catch (\Exception $e) {
                $reversIoOrderImportResponse->increaseTotalFailed();
            }
        }
        return $reversIoOrderImportResponse;
    }

    public function importOrder($idOrder)
    {
        try {
            $ordersBody = $this->ordersImportRequestService->getOrderInformationForImport($idOrder);
            $response = $this->reversIoApiConnect->importOrderRequest($ordersBody);
        } catch (\Exception $e) {
            throw new \Exception('Order import failed');
        }

        return $response;
    }
}
