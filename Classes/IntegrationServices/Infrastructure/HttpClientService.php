<?php

namespace WebanUg\Cleverreach\IntegrationServices\Infrastructure;

use CleverReach\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\Infrastructure\ServiceRegister;
use CleverReach\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\Infrastructure\Utility\HttpResponse;

class HttpClientService extends HttpClient
{
    /**
     * @var resource
     */
    protected $curlSession;

    /**
     * @inheritdoc
     */
    public function sendHttpRequest($method, $url, $headers = [], $body = '')
    {
        $this->setCurlSessionAndCommonRequestParts($method, $url, $headers, $body);
        $this->setCurlSessionOptionsForSynchronousRequest();

        return $this->executeAndReturnResponseForSynchronousRequest($url);
    }

    /**
     * @inheritdoc
     */
    public function sendHttpRequestAsync($method, $url, $headers = [], $body = '')
    {
        $this->setCurlSessionAndCommonRequestParts($method, $url, $headers, $body);
        $this->setCurlSessionOptionsForAsynchronousRequest();

        return curl_exec($this->curlSession);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     */
    private function setCurlSessionAndCommonRequestParts($method, $url, array $headers, $body)
    {
        $this->initializeCurlSession();
        $this->setCurlSessionOptionsBasedOnMethod($method);
        $this->setCurlSessionUrlHeadersAndBody($url, $headers, $body);
        $this->setCommonOptionsForCurlSession();
    }

    private function initializeCurlSession()
    {
        $this->curlSession = curl_init();
    }

    /**
     * @param string $method
     */
    private function setCurlSessionOptionsBasedOnMethod($method)
    {
        if ($method === 'DELETE') {
            curl_setopt($this->curlSession, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        if ($method === 'POST') {
            curl_setopt($this->curlSession, CURLOPT_POST, true);
        }

        if ($method === 'PUT') {
            curl_setopt($this->curlSession, CURLOPT_CUSTOMREQUEST, 'PUT');
        }
    }

    /**
     * @param string $url
     * @param array $headers
     * @param string $body
     */
    private function setCurlSessionUrlHeadersAndBody($url, array $headers, $body)
    {
        curl_setopt($this->curlSession, CURLOPT_URL, $url);
        curl_setopt($this->curlSession, CURLOPT_HTTPHEADER, $headers);
        if (!empty($body)) {
            curl_setopt($this->curlSession, CURLOPT_POSTFIELDS, $body);
        }
    }

    private function setCommonOptionsForCurlSession()
    {
        curl_setopt($this->curlSession, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlSession, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->curlSession, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->curlSession, CURLOPT_SSL_VERIFYHOST, false);
    }

    private function setCurlSessionOptionsForSynchronousRequest()
    {
        curl_setopt($this->curlSession, CURLOPT_HEADER, true);
    }

    private function setCurlSessionOptionsForAsynchronousRequest()
    {
        /** @var \WebanUg\Cleverreach\IntegrationServices\Infrastructure\ConfigurationService $configService */
        $configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        $timeout = $configService->getAsyncProcessRequestTimeout();
        // Always ensure the connection is fresh
        curl_setopt($this->curlSession, CURLOPT_FRESH_CONNECT, true);
        // Timeout super fast once connected, so it goes into async
        curl_setopt($this->curlSession, CURLOPT_TIMEOUT_MS, $timeout);
    }

    /**
     * @param string $url
     *
     * @return HttpResponse
     * @throws HttpCommunicationException
     */
    protected function executeAndReturnResponseForSynchronousRequest($url)
    {
        $apiResponse = curl_exec($this->curlSession);
        $statusCode = curl_getinfo($this->curlSession, CURLINFO_HTTP_CODE);
        curl_close($this->curlSession);

        if ($apiResponse === false) {
            throw new HttpCommunicationException('Request ' . $url . ' failed.');
        }

        $apiResponse = $this->strip100Header($apiResponse);

        return new HttpResponse(
            $statusCode,
            $this->getHeadersFromCurlResponse($apiResponse),
            $this->getBodyFromCurlResponse($apiResponse)
        );
    }

    /**
     * @param string $response
     *
     * @return bool|string
     */
    protected function strip100Header($response)
    {
        $delimiter = "\r\n\r\n";
        $needle = 'HTTP/1.1 100';
        if (strpos($response, $needle) === 0) {
            return substr($response, strpos($response, $delimiter) + 4);
        }

        return $response;
    }

    /**
     * @param string $response
     * @return array
     */
    protected function getHeadersFromCurlResponse($response)
    {
        $headers = [];
        $headersBodyDelimiter = "\r\n\r\n";
        $headerText = substr($response, 0, strpos($response, $headersBodyDelimiter));
        $headersDelimiter = "\r\n";

        foreach (explode($headersDelimiter, $headerText) as $i => $line) {
            if ($i === 0) {
                $headers[] = $line;
            } else {
                list($key, $value) = explode(': ', $line);
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    /**
     * @param string $response
     * @return string
     */
    protected function getBodyFromCurlResponse($response)
    {
        $headersBodyDelimiter = "\r\n\r\n";
        $bodyStartingPositionOffset = 4; // number of special signs in delimiter;
        return substr(
            $response,
            strpos($response, $headersBodyDelimiter) + $bodyStartingPositionOffset
        );
    }
}
