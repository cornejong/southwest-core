<?php

namespace SouthCoast\SouthWest\Base;

use SouthCoast\SouthWest\Core\Config;

abstract class Response
{
    private $isFormatted = false;

    protected $responseHeaders = [];
    protected $responseCode = 200;

    abstract public function formatResponse();
    abstract protected function sendData();

    final protected function sendResponseHeaders()
    {
        if (headers_sent()) {
            throw new \Exception('Headers Already Send!', 1);
        }

        foreach ($this->responseHeaders as $type => $value) {
            header($type . ': ' . $value);
        }

        return $this;
    }

    final protected function sendResponseCode()
    {
        http_response_code($this->responseCode);
    }

    final public function send()
    {
        /* Check if we have a response string */
        if (isset($this->isFormatted)) {
            /* If not, format that first */
            $this->formatResponse();
        }

        /* Set the default response headers */
        $this->setHeaders(Config::get('app.default.response_headers'));

        /* set the response headers */
        $this->sendResponseHeaders();
        /* Set the response Code */
        $this->sendResponseCode();
        /* Print the response string out to the client */
        $this->sendData();
    }

    /**
     * Getters/Setters
     */

    final public function setHeader(string $type, string $value)
    {
        $this->responseHeaders[$type] = $value;
        return $this;
    }

    final public function setHeaders(array $headers)
    {
        foreach ($headers as $type => $value) {
            $this->setHeader($type, $value);
        }

        return true;
    }

    final public function setCode(int $code)
    {
        $this->responseCode = $code;

        return $this;
    }

}
