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

use Configuration;
use GuzzleHttp\Exception\ClientException;
use ReversIO\Config\Config;
use ReversIO\Response\ReversIoResponse;
use ReversIO\Services\Decoder\Decoder;

class Token
{
    /** @var Decoder */
    private $decoder;

    public function __construct(Decoder $decoder)
    {
        $this->decoder = $decoder;
    }

    /**
     * This function is retrieving the token from Revers.io
     * https://demo-api-portal.revers.io/docs/services/revers/operations/GetToken?
     */
    public function getToken($apiPublicKey = null, $apisecretKey = null)
    {
        $client = new \GuzzleHttp\Client();
        $response = new ReversIoResponse();

        if ($apiPublicKey === null && $apisecretKey === null) {
            $apiPublicKey = Configuration::get(Config::PUBLIC_KEY);
            $apisecretKey = $this->decoder->base64Decoder(Configuration::get(Config::SECRET_KEY));
        }

        $apiUrlBase = Config::API_URL_BASE_LIVE;

        $isTestModeEnabled = (bool) Configuration::get(Config::TEST_MODE_SETTING);
        if ($isTestModeEnabled) {
            $apiUrlBase = Config::API_URL_BASE_DEMO;
        }

        try {
            $request = $client->get($apiUrlBase.'token?secret='.$apisecretKey, [
                'headers' => [
                    'Ocp-Apim-Subscription-Key' => $apiPublicKey,
                ]
            ]);

            $response->setSuccess(true);
            $response->setContent(json_decode($request->getBody()->__toString(), true));
        } catch (ClientException $exception) {
            $response->setSuccess(false);
            $response->setMessage($exception->getMessage());
        }

        return $response;
    }
}
