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
use ReversIO\Repository\Logs\Logger;
use ReversIO\Repository\Logs\LogsRepository;

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

    /** @var Logger */
    private $logger;

    public function __construct(
        OrdersRequestBuilder $ordersImportRequestService,
        ReversIOApi $reversIoApiConnect,
        OrderRepository $orderRepository,
        Logger $logger
    ) {
        $this->ordersImportRequestService = $ordersImportRequestService;
        $this->reversIoApiConnect = $reversIoApiConnect;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
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
                $owner = $this->importOwner($order['id_order']);
                $orderImportData = $this->ordersImportRequestService->getOrderImportData($order['id_order']);
                $importOrderResponse = $this->reversIoApiConnect->importOrderRequest($orderImportData,$owner);

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
            $owner = $this->importOwner($idOrder);
            $this->logger->insertOrderLogs(
                "OrderImportService::importOrder-owner-order-".$idOrder,
                json_encode($owner)
            );
            $ordersBody = $this->ordersImportRequestService->getOrderInformationForImport($idOrder,$owner);
            $this->logger->insertOrderLogs(
                "OrderImportService::importOrder-order-".$idOrder,
                json_encode($ordersBody)
            );
            $response = $this->reversIoApiConnect->importOrderRequest($ordersBody);

            if(!$response->isSuccess()){
                $this->logger->insertOrderLogs(
                    "OrderImportService::importOrder-Fin".$idOrder,
                    "Erreur lors de l'import de commande"
                );
            }else{
                $this->logger->insertOrderLogs(
                    "OrderImportService::importOrder-Fin".$idOrder,
                    "Commande importé"
                );
            }
        } catch (\Exception $e) {
            $this->logger->insertOrderLogs(
                "OrderImportService::importOrder-error-orderId-".$idOrder,
                $e->getMessage()
            );
            throw new \Exception('Order import failed');
        }

        return $response;
    }

    public function importOwner($idOrder){
        try{
            $ownerBody = $this->ordersImportRequestService->getCustomerByOrderId($idOrder);
            if($ownerBody != null){
                if(empty($ownerBody['address1']) || empty($ownerBody['group_name'])){
                    return null;
                }   
                $response = $this->reversIoApiConnect->putOwner($ownerBody);
                if($response->isSuccess()){
                    return $ownerBody;
                }
            }

            return null;
        }catch(\Exception $e){
            return null;
        }
    }
}
