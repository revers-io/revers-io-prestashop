<?php
namespace ReversIO\Services\Orders;
use ReversIO\Services\APIConnect\ReversIOApi;

class OrdersRetrieveService
{
    private $reversIoApiConnect;

    public function __construct(ReversIOApi $api = null)
    {
        $this->reversIoApiConnect = $api;
    }

    public function setApi(ReversIOApi $api)
    {
        $this->reversIoApiConnect = $api;
    }

    public function getRetrievedOrder($orderReference)
    {
        return $this->reversIoApiConnect->retrieveOrder($orderReference);
    }
}




