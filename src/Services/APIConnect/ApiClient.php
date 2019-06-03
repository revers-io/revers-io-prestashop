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

namespace ReversIO\Services\APIConnect;

use GuzzleHttp\Exception\ClientException;
use ReversIO\Factory\ClientFactory;
use ReversIO\Proxy\ApiClientInterface;
use ReversIO\Response\ReversIoResponse;

class ApiClient implements ApiClientInterface
{
    /** @var ClientFactory */
    private $client;

    public function __construct(ClientFactory $client)
    {
        $this->client = $client;
    }

    public function get($url, $headers)
    {
        $client = $this->client->getClient();

        $response = new ReversIoResponse();

        try {
            $request = $client->get($url, $headers);
            $response->setSuccess(true);
            $response->setContent(json_decode($request->getBody()->__toString(), true));
        } catch (ClientException $exception) {
            $response->setSuccess(false);
            $response->setMessage($exception->getMessage());
            throw $exception;
        }

        return $response;
    }

    public function put($url, $requestHeadersAndBody)
    {
        $client = $this->client->getClient();

        $response = new ReversIoResponse();

        try {
            $request = $client->put($url, $requestHeadersAndBody);
            $response->setSuccess(true);
            $response->setContent(json_decode($request->getBody()->__toString(), true));
        } catch (ClientException $exception) {
            $response->setSuccess(false);
            $response->setMessage($exception->getMessage());
            throw $exception;
        }
        return $response;
    }

    public function post($url, $headers)
    {
        $client = $this->client->getClient();

        $response = new ReversIoResponse();

        try {
            $request = $client->post($url, $headers);
            $response->setSuccess(true);
            $response->setContent(json_decode($request->getBody()->__toString(), true));
        } catch (ClientException $exception) {
            $response->setSuccess(false);
            $response->setMessage($exception->getMessage());
            throw $exception;
        }
        return $response;
    }
}
