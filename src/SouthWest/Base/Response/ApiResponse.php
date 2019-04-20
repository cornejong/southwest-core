<?php

namespace SouthCoast\SouthWest\Base\Response;

use SouthCoast\Helpers\Identifier;
use SouthCoast\Helpers\Json;
use SouthCoast\Helpers\Spice;
use SouthCoast\Helpers\Xml;
use SouthCoast\SouthWest\Base\Response as ResponseBase;
use SouthCoast\SouthWest\Core\Config;
use SouthCoast\SouthWest\Core\Request;

class ApiResponse extends ResponseBase
{
    const RESPOND_JSON = 'json';
    const RESPOND_XML = 'xml';

    const ACCEPTED_FORMATS = [
        ApiResponse::RESPOND_JSON,
        ApiResponse::RESPOND_XML,
    ];

    public function __construct(array $data, int $code = 200, string $format = null)
    {
        $this->setResponseFormat(is_null($format) ? Config::get('api.default.response_format') : $format);

        $this->responseBody = $data;

        $this->setCode($code);

        $this->setHeaders([
            'Content-Type' => 'application/' . $this->responseFormat,
        ]);

        $this->formatResponse();
    }

    public function formatResponse()
    {
        switch ($this->responseFormat) {
            case ApiResponse::RESPOND_JSON:
                $data = Config::get('api.base_response');
                if (!$data) {
                    $data = $this->responseBody;
                } else {
                    $data = $this->addAdditionalData($data);
                }

                $this->responseString = Json::prettyEncode($data);
                $this->isFormatted = true;
                break;

            case ApiResponse::RESPOND_XML:
                $this->responseString = Xml::encode('SouthWest', $this->responseBody);
                $this->isFormatted = true;
                break;
        }
    }

    private function addResponseIdToResponse(array $data)
    {
        $id = '';

        switch (Config::get('api.default.response_id_format')) {
            case 'hash':
                $tmp = $data;
                unset($tmp['response_id']);
                $id = Spice::Tighten(serialize($tmp), (string) time());
                break;

            case 'guid':
                $id = Identifier::newGuid();
                break;

            case 'uid':
                $id = Identifier::newUniqId();
                break;

            default:
                break;
        }

        $data['response_id'] = $id;

        return $data;
    }

    protected function addAdditionalData($base)
    {
        if (!Config::get('api.autofill_fields')) {
            $base[Config::get('api.response_body_tag')] = $this->responseBody;
            return $base;
        }

        foreach ($base as $key => $value) {
            switch ($key) {
                case Config::get('api.response_body_tag'):
                    $base[$key] = $this->responseBody;
                    break;

                case 'status_code':
                    $base[$key] = $this->responseCode;
                    break;

                case 'success':
                    $base[$key] = ($this->responseCode !== 200) ? false : true;
                    break;

                case 'request':
                    $base[$key] = $this->buildOriginalRequestArray();
                    break;

                case 'timestamp':
                    $base[$key] = time();
                    break;

                case 'process_time':
                    $base[$key] = round(abs(microtime(true) - Request::$start_time_float), 2);
                    break;

                case 'version':
                    $base[$key] = Config::get('api.version');
                    break;

                default:
                    # code...
                    break;
            }
        }

        if (array_key_exists('response_id', $base)) {
            $base = $this->addResponseIdToResponse($base);
        }

        return $base;
    }

    public function buildOriginalRequestArray()
    {
        return [
            /* The requested Endpoint */
            'endpoint' => Request::$raw_path,
            /* The provided query */
            'query' => empty(Request::$query) ? null : Request::$query,
            /* The request Method */
            'method' => Request::$method,
            /* If the request was authenticated or not (does not care if it needed to be) */
            'authenticated' => true,
        ];
    }

    protected function sendData()
    {
        print($this->responseString);
    }

    public function setResponseFormat(string $format)
    {
        if (!in_array($format, ApiResponse::ACCEPTED_FORMATS)) {
            throw new \Exception('Unsuported response format', 1);
        }

        $this->responseFormat = $format;
    }
}
