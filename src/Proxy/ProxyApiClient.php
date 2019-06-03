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

namespace ReversIO\Proxy;

use Configuration;
use GuzzleHttp\Exception\ClientException;
use ReversIO\Config\Config;
use ReversIO\Services\APIConnect\Token;

class ProxyApiClient implements ApiClientInterface
{
    /** @var Token */
    private $token;

    /** @var ApiClientInterface */
    private $apiClient;

    public function __construct(Token $token, ApiClientInterface $apiClient)
    {
        $this->token = $token;
        $this->apiClient = $apiClient;
    }

    public function get($url, $headers)
    {
        $apiPublicKey = Configuration::get(Config::PUBLIC_KEY);
        $apiSecretKey = Configuration::get(Config::SECRET_KEY);

        try {
            return $this->apiClient->get($url, $headers);
        } catch (ClientException $exception) {
            $statusCode = $exception->getCode();

            if ($statusCode === 401) {
                $tokenRequest = $this->token->getToken($apiPublicKey, $apiSecretKey);
                if ($tokenRequest->isSuccess()) {
                    return $this->apiClient->get($url, $headers);
                }
            }

            throw $exception;
        }
    }

    public function post($url, $headers)
    {
        $apiPublicKey = Configuration::get(Config::PUBLIC_KEY);
        $apiSecretKey = Configuration::get(Config::SECRET_KEY);

        try {
            return $this->apiClient->post($url, $headers);
        } catch (ClientException $exception) {
            $statusCode = $exception->getCode();

            if ($statusCode === 401) {
                $tokenRequest = $this->token->getToken($apiPublicKey, $apiSecretKey);
                if ($tokenRequest->isSuccess()) {
                    return $this->apiClient->post($url, $headers);
                }
            }

            throw $exception;
        }
    }

    public function put($url, $headers)
    {
        $apiPublicKey = Configuration::get(Config::PUBLIC_KEY);
        $apiSecretKey = Configuration::get(Config::SECRET_KEY);

        try {
            return $this->apiClient->put($url, $headers);
        } catch (ClientException $exception) {
            $statusCode = $exception->getCode();

            if ($statusCode === 401) {
                $tokenRequest = $this->token->getToken($apiPublicKey, $apiSecretKey);
                if ($tokenRequest->isSuccess()) {
                    return $this->apiClient->put($url, $headers);
                }
            }

            throw $exception;
        }
    }
}
