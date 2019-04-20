<?php

namespace SouthCoast\SouthWest\Base;

use SouthCoast\SouthWest\Base\Response;
use SouthCoast\SouthWest\Base\Response\ViewResponse;

abstract class Controller
{
    abstract public static function _behaviour();
    abstract public static function _rules();
    abstract public static function _access();

    final public function _handle(string $method, array $params = [])
    {
        if (method_exists($this, '_init')) {
            $this->_init();
        }

        $this->_runPreExecutionMethod($method, $params);

        $response = $this->{$method}(...$params);

        if (!$response instanceof Response) {
            /* TODO: Throw Invalid Response Error (500) */
            throw new \InvalidResponseError("There was an invalid response provided!", 1);
        }

        return $response;
    }

    protected function _runPreExecutionMethod(string $method, array $params)
    {
        if (isset($this->_rules()[$method]['pre_execution'])) {
            $pre_execution_method = $this->_rules()[$method]['pre_execution'];
            $this->{$pre_execution_method}(...$params);
        }
    }

    protected function render(string $view, array $parameters = [])
    {
        return new ViewResponse($view, $parameters);
    }
}
