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

use Product;
use Country;
use ReversIO\Config\Config;
use ReversIO\Repository\OrderRepository;
use ReversIO\Services\APIConnect\ReversIOApi;
use ReversIOIntegration;

class OrdersRequestBuilder
{
    /** @var OrderRepository */
    private $orderRepository;

    /**
     * @var ReversIOIntegration
     */
    private $module;

    public function __construct(OrderRepository $orderRepository, ReversIOIntegration $module)
    {
        $this->orderRepository = $orderRepository;
        $this->module = $module;
    }

    public function getOrdersInformationForImport()
    {
        $orders = $this->getOrdersForImports();

        $array = [];

        foreach ($orders as $order) {
            $orderObject = new \Order($order['id_order']);

            $modelIdArray = $this->getModelsIds($order['id_order']);

            if (empty($modelIdArray)) {
                continue;
            }

            $currency = new \Currency($orderObject->id_currency);

//            if ($currency->iso_code !== Config::CURRENCY_EUR && $currency->iso_code !== Config::CURRENCY_GBP) {
//                continue;
//            }

            $customerObject = new \Customer($orderObject->id_customer);
            $addressObject = new \Address($orderObject->id_address_delivery);

            $country = new Country($addressObject->id_country);

            $dateTimeObject = new \DateTime($orderObject->date_add);

            $dateTimeZone = new \DateTimeZone('UTC');

            $purchaseDateUtc = $dateTimeObject->setTimezone($dateTimeZone);

            $array[] = [
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
        }

        return $array;
    }

    private function getProductModelId($productId)
    {
        $modelId = 0;
        /** @var ReversIOApi $reversIOAPIConnect */
        $reversIOAPIConnect = $this->module->getContainer()->get('reversIoApiConnect');
        $listModels = $reversIOAPIConnect->getListModels();

        if ($listModels->isSuccess()) {
            $product = new Product($productId);
            $reference = $product->reference;

            foreach ($listModels->getContent()['value'] as $listModel) {
                if (isset($listModel['sku'])) {
                    if ($listModel['sku'] == $reference) {
                        $modelId = $listModel['id'];
                    }
                }
            }
        }

        return $modelId;
    }

    private function getOrdersForImports()
    {
        $orderIds = [];

        $orders = $this->orderRepository->getOrders();

        foreach ($orders as $order) {
            if ($order['revers_io_order'] === null) {
                $orderIds[] = [
                    'id_order' => $order['ps_order'],
                ];
            }
        }

        return $orderIds;
    }

    private function getModelsIds($orderId)
    {
        $productsId = $this->orderRepository->getOrderedProductId($orderId);
        $orderObject = new \Order($orderId);
        $modelIdArray = [];

        foreach ($productsId as $productId) {
            $modelId = $this->getProductModelId($productId['product_id']);

            if ($modelId === 0) {
                continue;
            }

            $modelIdArray[] =
                [
                    'modelId' => $modelId,
                    'price' => [
                        'amount' => $orderObject->total_paid_tax_incl,
                        'currency' => 'EUR',
                    ],
                    'orderLineReference' => $orderObject->reference,
                ];
        }

        return $modelIdArray;
    }
}
