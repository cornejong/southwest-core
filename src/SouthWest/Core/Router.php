<?php

namespace SouthCoast\SouthWest\Core;

use SouthCoast\SouthWest\Core\Request;
use SouthCoast\SouthWest\Core\Route;
use SouthCoast\SouthWest\Throwables\HttpError;

class Router
{
    /**
     * @var mixed
     */
    public static $request;

    public function handle()
    {
        Request::parse();

        /* Check if the route is known, and pass $route by reference */
        /* This will be filled when a route is found, stays empty when not */
        if (!Route::isKnown(Request::$path, $route)) {
            throw new HttpError(HttpError::NOT_FOUND);
        }

        if (!$route->acceptsRequestMethod(Request::$method)) {
            throw new HttpError(HttpError::METHOD_NOT_ALLOWED);
        }

        switch ($route->type) {
            case Route::ACTION_CONTROLLER:

                if (!$route->hasAccess()) {
                    throw new HttpError(HttpError::UNAUTHORIZED);
                }

                $controller = $route->getController();
                $response = call_user_func([$controller, '_handle'], $route->getMethod(), $route->getVariableValues());
                $response->send();
                break;

            case Route::ACTION_REDIRECT:
                /* TODO: Build this functionality */
                Router::setHeader('Location', $route->location);
                break;
        }

        # code...
    }

    /**
     * @param array $headers
     */
    public static function setHeaders(array $headers)
    {
        foreach ($headers as $headerType => $headerValue) {
            self::setHeader($headerType, $headerValue);
        }
    }

    /**
     * @param string $headerType
     * @param string $headerValue
     */
    public static function setHeader(string $headerType, string $headerValue)
    {
        header($headerType . ': ' . $headerValue);
    }
}
