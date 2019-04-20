<?php

namespace SouthCoast\SouthWest\Core;

use SouthCoast\Helpers\ArrayHelper;
use SouthCoast\Helpers\Env;
use SouthCoast\Helpers\Identifier;
use SouthCoast\Helpers\StringHelper;
use SouthCoast\Helpers\Validate;
use SouthCoast\SouthWest\Component\Route as RouteObject;

/**
 * Handles All Route related storage
 *
 * @todo: add RoutesError Class
 */
class Route
{
    /**
     * Matching pattern elements
     */

    const PATH_SEPARATOR = '/';

    const PATH_SEPARATOR_EXPRESSION = '\/';
    const PATH_SEPARATOR_OR_NOT_EXPRESSION = '(\/|)';
    const REQUIRED_VARIABLE_EXPRESSION = '([^\/\s]*)';
    const OPTIONAL_VARIABLE_EXPRESSION = '(([^\/\s]*)|)';
    const STRICT_VALUE_OPENER_EXPRESSION = '(';
    const STRICT_VALUE_CLOSER_EXPRESSION = ')';

    const MATCHING_PATTERN_CLOSER = self::PATH_SEPARATOR_OR_NOT_EXPRESSION . '$/';
    const MATCHING_PATTERN_OPENER = '/^' . self::PATH_SEPARATOR_EXPRESSION;

    const ACTION_CONTROLLER = 'controller';
    const ACTION_REDIRECT = 'redirect';

    const CONTROLLER_ACTIONS = 'CA_' . 1;
    const NAMESPACE_SEPARATOR = '\\';
    const VARIABLE_INDICATOR = '$';
    const OPTIONAL_INDICATOR = '*';
    const ACCEPTED_REQUEST_METHODS = [
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTION',
    ];

    const ALL_REQUEST_METHODS = self::ACCEPTED_REQUEST_METHODS;

    public static $routes = [];

    /**
     * @var mixed
     */
    private static $base_route_string;
    /**
     * @var mixed
     */
    private static $base_route_array;

    /**
     * @var mixed
     */
    private static $case_insensitive = false;

    /**
     * @var mixed
     */
    private static $current;

    /**
     * @param $methods
     * @param mixed $route
     * @param string $controller
     * @param string $method
     * @return mixed
     */
    public static function set($methods, $route, string $controller, string $method)
    {
        /* Check if the request method is a string */
        if (is_string($methods)) {
            /* Wrap it in an array */
            $method = [$methods];
        }

        /* CleanUp all entries */
        ArrayHelper::walk($methods, function (&$element) {
            $element = trim(strtoupper($element));
        });

        if (is_string($route)) {
            $route = explode(Route::PATH_SEPARATOR, $route);

            /* CleanUp all entries */
            ArrayHelper::walk($route, function (&$element) {
                $element = trim(strtoupper($element));
            });
        }

        /* Check if the provided request methods are allowed */
        if (ArrayHelper::containsUnAcceptedElements($methods, self::ACCEPTED_REQUEST_METHODS, $unsupported)) {
            throw new RoutesError(RoutesError::UNSUPPORTED_METHOD_PROVIDED, $unsupported);
        }

        /* Check if the provided controller is in a different namespace than the Controller namespace */
        if (!StringHelper::contains(self::NAMESPACE_SEPARATOR, $controller)) {
            $controller = 'App\\Controller\\' . $controller;
        }

        /* Check if the controller class exists */
        if (!class_exists($controller)) {
            throw new RoutesError(RoutesError::CLASS_NOT_EXIST, $controller);
        }

        /* Check if the controller method exists */
        if (!method_exists($controller, $method)) {
            throw new RoutesError(RoutesError::METHOD_NOT_EXIST, $controller . '->' . $method . '()');
        }

        /* Extract the variables from the provided route */
        $variables = self::extractRouteVariableKeys($route);

        $pattern = self::buildRouteMatchPattern($route, $variables);

        $identifier = Identifier::newGuid();

        self::$routes[$identifier] = new RouteObject([
            'id' => $identifier,
            'type' => Route::ACTION_CONTROLLER,
            'route' => $route,
            'match_pattern' => $pattern,
            'request_method' => $methods,
            'variables' => $variables,
            'handler' => [
                'controller' => $controller,
                'method' => $method,
            ],
        ]);

        return $identifier;
    }

    /**
     * @param mixed $route
     * @param string $to
     */
    public static function redirect($route, string $to)
    {
        if (!Validate::url($to)) {
            throw new RoutesError(RoutesError::INVALID_URL, $to);
        }

        if (is_string($route)) {
            $route = explode(Route::PATH_SEPARATOR, $route);

            /* CleanUp all entries */
            ArrayHelper::walk($route, function (&$element) {
                $element = trim(strtoupper($element));
            });
        }

        $pattern = self::buildRouteMatchPattern($route, self::extractRouteVariableKeys([]));

        $identifier = Identifier::newGuid();

        self::$routes[$identifier] = new RouteObject([
            'id' => $identifier,
            'type' => Route::ACTION_REDIRECT,
            'route' => $route,
            'match_pattern' => $pattern,
            'request_method' => Route::ACCEPTED_REQUEST_METHODS,
            'location' => $to,
        ]);

        return $identifier;
    }

    /**
     * @param array $route
     * @param array $variables
     * @return mixed
     */
    public static function buildRouteMatchPattern(array $route, array $variables): string
    {
        $pattern = self::MATCHING_PATTERN_OPENER;

        foreach ($route as $index => $element) {
            if (in_array($index, $variables['required'])) {
                $pattern .= self::REQUIRED_VARIABLE_EXPRESSION;
            } elseif (in_array($index, $variables['optional'])) {
                $pattern .= self::PATH_SEPARATOR_OR_NOT_EXPRESSION . self::OPTIONAL_VARIABLE_EXPRESSION;
            } else {
                $pattern .= self::STRICT_VALUE_OPENER_EXPRESSION . $element . self::STRICT_VALUE_CLOSER_EXPRESSION;
            }

            if (in_array($index + 1, $variables['optional'])) {
                /* Do Nothing */
            } elseif (in_array($index + 1, $variables['required'])) {
                $pattern .= self::PATH_SEPARATOR_EXPRESSION;
            } elseif ($index === (count($route) - 1)) {
                /* Do Nothing */
            } elseif ($index !== (count($route) - 1)) {
                $pattern .= self::PATH_SEPARATOR_EXPRESSION;
            }

        }

        $pattern .= self::MATCHING_PATTERN_CLOSER . (self::$case_insensitive ? 'i' : '');

        return $pattern;
    }

    /**
     * @param bool $insensitive
     */
    public static function caseInsensitive(bool $insensitive = true)
    {
        self::$case_insensitive = $insensitive;
    }

    /**
     * @param string $base
     */
    public static function base(string $base = null)
    {
        if (is_null($base) && is_null(self::$base_route_string)) {
            $base = Config::get('app.web.base_path');
        }

        if (is_null($base) && !is_null(self::$base_route_string)) {
            return self::$base_route_string;
        }

        if (StringHelper::startsWith('/', $base)) {
            $base = ltrim('/', $base);
        }

        if (StringHelper::endsWith('/', $base)) {
            $base = rtrim('/', $base);
        }

        self::$base_route_string = $base;
        self::$base_route_array = explode('/', $base);
    }

    /**
     * @param array $route
     * @return mixed
     */
    public static function extractRouteVariableKeys(array $route): array
    {
        $tmp = [
            'required' => [],
            'optional' => [],
        ];

        foreach ($route as $index => $item) {
            /* Check for variables */
            if (StringHelper::startsWith(self::VARIABLE_INDICATOR, $item)) {
                if (StringHelper::endsWith(self::OPTIONAL_INDICATOR, $item)) {
                    if ($index !== (count($route) - 1)) {
                        throw new RoutesError(RoutesError::OPTIONAL_ROUTE_TOKEN_NOT_ON_END, implode('/', $route));
                    }

                    $tmp['optional'][] = $index;
                } else {
                    $tmp['required'][] = $index;
                }
            }
        }

        return $tmp;
    }

    /**
     * @param string $path
     * @param $route
     */
    public static function isKnown(string $path, &$route): bool
    {
        $route = self::findMatch($path);
        return empty($route) ? false : true;
    }

    public static function load()
    {
        foreach (glob(Env::base_dir() . '/Routes/*.php') as $file) {
            require $file;
        }
    }

    public static function getAllRoutes()
    {
        return self::$routes;
    }

    /**
     * @param string $path
     * @return mixed
     */
    public static function findMatch(string $path)
    {
        foreach (self::$routes as $route) {
            if ($route->matches($path)) {
                self::$current = $route;
                return $route;
            }
        }
        return false;
    }

    public static function current(): RouteObject
    {
        return self::$current;
    }

    public static function hasAccess()
    {
        return GateKeeper::shallNotPass(self::$current) ? false : true;
    }
}
