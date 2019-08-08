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

use Country;
use Exception;
use ReversIO\Config\Config;
use ReversIO;
use ReversIO\Repository\Logs\Logger;
use ReversIO\Repository\Logs\LogsRepository;
use ReversIO\Repository\OrderRepository;
use ReversIO\Services\Product\ModelService;

class OrdersRequestBuilder
{
    /** @var OrderRepository */
    private $orderRepository;

    /**
     * @var ReversIO
     */
    private $module;

    /**
     * @var ModelService
     */
    private $modelService;

    /** @var OrderStatus */
    private $orderStatuses;

    /** @var Logger */
    private $logger;

    public function __construct(
        OrderRepository $orderRepository,
        ReversIO $module,
        ModelService $modelService,
        Logger $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->module = $module;
        $this->modelService = $modelService;
        $this->logger = $logger;

        $this->orderStatuses = new OrderStatus();
    }

    public function getOrderInformationForImport($orderId)
    {
        $orderId = $this->getOrderForImports($orderId);

        if (!$orderId) {
            return false;
        }

        try {
            $orderImportData = $this->getOrderImportData($orderId);
        } catch (\Exception $e) {
            throw new Exception('Order was not imported');
        }

//        if (!$orderImportData) {
//            return false;
//        }

        return $orderImportData;
    }

    public function getOrderImportData($idOrder)
    {
        $orderObject = new \Order($idOrder);

        $currency = new \Currency($orderObject->id_currency);

        if ($currency->iso_code !== Config::CURRENCY_EUR && $currency->iso_code !== Config::CURRENCY_GBP) {
            $this->logger->insertOrderLogs(
                $orderObject->reference,
                $this->module->l('Order is not imported because the currency is not EUR or GBP')
            );
            $this->orderRepository->insertOrdersByState(
                $orderObject->reference,
                Config::SYNCHRONIZED_UNSUCCESSFULLY_ORDERS_STATUS
            );
            $this->orderRepository->insertSuccessfullyOrNotSuccessfullyImportedOrder(
                $orderObject->reference,
                0
            );
            throw new Exception('Order is not imported because the currency is not EUR or GBP');
        }

        try {
            $modelIdArray = $this->modelService->getModelsIds($idOrder, $currency->iso_code);
        } catch (\Exception $e) {
            $this->logger->insertOrderLogs(
                $orderObject->reference,
                $this->module->l($e->getMessage())
            );
            $this->orderRepository->insertOrdersByState(
                $orderObject->reference,
                Config::SYNCHRONIZED_UNSUCCESSFULLY_ORDERS_STATUS
            );
            $this->orderRepository->insertSuccessfullyOrNotSuccessfullyImportedOrder(
                $orderObject->reference,
                0
            );
            throw new Exception($e->getMessage());
        }

        if (empty($modelIdArray)) {
            $this->logger->insertOrderLogs(
                $orderObject->reference,
                $this->module->l('The order was not imported because it has no products')
            );
            $this->orderRepository->insertOrdersByState(
                $orderObject->reference,
                Config::SYNCHRONIZED_UNSUCCESSFULLY_ORDERS_STATUS
            );
            $this->orderRepository->insertSuccessfullyOrNotSuccessfullyImportedOrder(
                $orderObject->reference,
                0
            );
            throw new Exception('The order was not imported because it has no products');
        }

        $customerObject = new \Customer($orderObject->id_customer);
        $addressObject = new \Address($orderObject->id_address_delivery);

        $country = new Country($addressObject->id_country);

        $dateTimeObject = new \DateTime($orderObject->date_add);

        $dateTimeZone = new \DateTimeZone('UTC');

        $purchaseDateUtc = $dateTimeObject->setTimezone($dateTimeZone);

        $orderImportData = [
            'orderReference' => $orderObject->reference,
            'civility' => 'NotSet',
            'customerLastName' => $customerObject->lastname,
            'customerFirstName' => $customerObject->firstname,
            'address' => [
                'companyName' => $addressObject->company,
                'streetAddress' => $addressObject->address1,
                'additionalAddress' => '',
                'doorCode' => '',
                'floor' => $addressObject->address2,
                'zipCode' => $addressObject->postcode,
                'city' => $addressObject->city,
                'countryCode' => $country->iso_code,
            ],
            'phoneNumber' => $addressObject->phone,
            'customerMail' => $customerObject->email,
            'purchaseDateUtc' => $purchaseDateUtc->format("Y-m-d H:i:s"),
            'products' => $modelIdArray,
            'shippingPrice' => [
                'amount' => $orderObject->total_shipping_tax_incl,
                'currency' => $currency->iso_code,
            ],
            'salesChannel' => "",
        ];

        return $orderImportData;
    }

    public function getOrdersForImports($limit)
    {
        $orderIds = [];

        $orderStatusesForImport = $this->orderStatuses->getOrderStatusForImport();

        if (!$orderStatusesForImport) {
            return $orderIds;
        }

        $dateFrom = \Configuration::get(Config::ORDER_DATE_FROM);
        $dateTo = \Configuration::get(Config::ORDER_DATE_TO);

        $orders = $this->orderRepository->getOrdersForImport($orderStatusesForImport, $limit, $dateFrom, $dateTo);

        foreach ($orders as $order) {
            $orderIds[] = [
                'id_order' => $order['ps_order'],
            ];
        }

        return $orderIds;
    }

    private function getOrderForImports($orderId)
    {
        $orderStatusesForImport = $this->orderStatuses->getOrderStatusForImport();

        $order = $this->orderRepository->getOrderForImportById($orderId, $orderStatusesForImport);

        if ($order) {
            return $order;
        }

        return false;
    }
}
