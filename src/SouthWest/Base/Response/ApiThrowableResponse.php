<?php

namespace SouthCoast\SouthWest\Base\Response;

use SouthCoast\Helpers\Env;
use SouthCoast\SouthWest\Core\Config;
use SouthCoast\SouthWest\Throwables\HttpError;

class ApiThrowableResponse extends ApiResponse
{
    const SEVERITY = [
        1 => 'E_ERROR',
        2 => 'E_WARNING',
        4 => 'E_PARSE',
        8 => 'E_NOTICE',
        16 => 'E_CORE_ERROR',
        32 => 'E_CORE_WARNING',
        64 => 'E_COMPILE_ERROR',
        128 => 'E_COMPILE_WARNING',
        256 => 'E_USER_ERROR',
        512 => 'E_USER_WARNING',
        1024 => 'E_USER_NOTICE',
        2048 => 'E_STRICT',
        4096 => 'E_RECOVERABLE_ERROR',
        8192 => 'E_DEPRECATED',
        16384 => 'E_USER_DEPRECATED',
    ];

    /**
     * @param \Throwable $th
     */
    public function __construct(\Throwable $th)
    {
        $this->setResponseFormat(Config::get('api.default.response_format') ?? 'json');

        if ($th instanceof HttpError) {
            $body = $th->getBody();
            $this->setCode($th->status_code);
        } elseif ($th instanceof \ErrorException) {
            $this->setCode(500);
            $body = [
                'type' => self::SEVERITY[$th->getSeverity()],
                'message' => $th->getMessage(),
                'trace' => explode("\n", $th->getTraceAsString()),
            ];
        } else {
            $this->setCode(500);
            $body = [
                'type' => get_class($th),
                'message' => $th->getMessage(),
                'trace' => explode("\n", $th->getTraceAsString()),
            ];
        }

        if (!Env::isDev()) {
            unset($body['trace']);
        }

        $this->responseBody = $body;

        $this->setHeaders([
            'Content-Type' => 'application/' . $this->responseFormat,
        ]);

        $this->formatResponse();
    }
}
