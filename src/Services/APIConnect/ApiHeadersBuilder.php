<?php

namespace ReversIO\Services\APIConnect;

use Configuration;
use ReversIO\Config\Config;

class ApiHeadersBuilder
{
    private $token;

    private $apiPublic;

    private $authorization;

    public function __construct(Token $token)
    {
        $this->token = $token;
        $this->apiPublic = Configuration::get(Config::PUBLIC_KEY);
        $this->authorization = 'Bearer '.$this->token->getToken()->getContent()['value'];
    }

    public function buildHeadersForPutAndPost()
    {
        return [
            'Authorization' => $this->authorization,
            'Content-Type' => 'application/json',
            'Ocp-Apim-Subscription-Key' => $this->apiPublic
        ];
    }

    public function buildHeadersForGet()
    {
        return [
            'Authorization' => $this->authorization,
            'Ocp-Apim-Subscription-Key' => $this->apiPublic,
        ];
    }

    public function buildHeadersForGetWithLanguage($langIsoCode)
    {
        return [
            'Accept-Language' => $langIsoCode,
            'Authorization' => $this->authorization,
            'Ocp-Apim-Subscription-Key' => $this->apiPublic,
        ];
    }
}
