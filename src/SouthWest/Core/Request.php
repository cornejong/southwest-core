<?php

namespace SouthCoast\SouthWest\Core;

use SouthCoast\Helpers\Json;
use SouthCoast\Helpers\Xml;
use SouthCoast\SouthWest\Core\Route;

class Request
{
    const PATH_SEPARATOR = '/';
    const NOTHING = '';

    /**
     * @var mixed
     */
    public static $isParsed = false;

    /**
     * @var mixed
     */
    public static $raw_path;
    /**
     * @var mixed
     */
    public static $path_array;
    /**
     * @var mixed
     */
    public static $path;

    /**
     * @var mixed
     */
    public static $query;
    /**
     * @var mixed
     */
    public static $headers;

    /**
     * @var mixed
     */
    public static $body;
    /**
     * @var string
     */
    public static $body_parse_error = '';

    /**
     * @var mixed
     */
    public static $authenticationProvided = false;
    /**
     * @var mixed
     */
    public static $authenticationType;
    /**
     * @var mixed
     */
    public static $authenticationValue;

    /**
     * @var mixed
     */
    public static $method;
    /**
     * @var mixed
     */
    public static $host;
    /**
     * @var mixed
     */
    public static $client_ip;
    /**
     * @var mixed
     */
    public static $start_time;
    /**
     * @var mixed
     */
    public static $start_time_float;

    public static function parse()
    {
        self::parseUri();
        self::parseQuery();

        self::parseServer();

        self::parseHeaders();
        self::parseAuthenticationHeader();

        self::parseBody();

        self::$isParsed = true;
    }

    /**
     * Parsers
     */

    public static function parseUri()
    {
        /* Get the request based on the uri */
        $request = parse_url($_SERVER['REQUEST_URI']);
        /* Store the raw path */
        self::$raw_path = $request['path'];
        /* Save the uri string */
        self::$path = str_replace(Route::base(), self::NOTHING, substr($request['path'], 1));
        /* Explode it by te path SEPARATOR */
        foreach (explode(self::PATH_SEPARATOR, self::$path) as $item) {
            if (!empty($item)) {
                self::$path_array[] = $item;
            }
        }
    }

    public static function parseQuery()
    {
        /* Get the Query! */
        self::$query = $_GET;
    }

    public static function parseServer()
    {
        /* Get the reqeuest method */
        self::$method = trim(strtoupper($_SERVER['REQUEST_METHOD']));
        /* Lets save the host aswell */
        self::$host = $_SERVER['HTTP_HOST'];
        /* Maybe this can come in handy :) */
        self::$client_ip = $_SERVER['REMOTE_ADDR'];
        /* Always good to know when you started */
        self::$start_time = $_SERVER['REQUEST_TIME'];
        /* Also save the flaot value */
        self::$start_time_float = $_SERVER['REQUEST_TIME_FLOAT'];
    }

    public static function parseHeaders()
    {
        /* Lets just get all of them */
        self::$headers = getallheaders();
    }

    /**
     * @return null
     */
    public static function parseBody()
    {
        /* Get the raw body */
        $content = file_get_contents('php://input');

        /* Check if the body is empty */
        if (empty($content) || empty($_POST)) {
            /* Set the body to null */
            self::$body = null;
            /* return */
            return;
        }

        /* Switch based on the content type */
        switch (self::header('Content-Type')) {
            /* If it's form data */
            case 'application/x-www-form-urlencoded':
                /* Just use the POST variable in php */
                self::$body = $_POST;
                break;

            /* If its Json Data */
            case 'application/json':
                try {
                    /* Parse it to an array */
                    self::$body = Json::parseToArray($content);
                } catch (\Throwable $th) {
                    /* If we caught anything, set the body to false */
                    self::$body = false;
                    self::$body_parse_error = $th->getMessage();
                }
                break;

            /* If its XML */
            case 'application/xml':
            case 'text/xml':
                try {
                    /* Parse it to an array */
                    self::$body = Xml::parseToArray($content);
                } catch (\Throwable $th) {
                    /* If we caught anything, set the body to false */
                    self::$body = false;
                    self::$body_parse_error = $th->getMessage();
                }
                break;

            /* If non of the above */
            default:
                /* Set the body to false */
                self::$body = false;
                self::$body_parse_error = 'No supported content-type provided!';
                break;
        }
    }

    public static function parseAuthenticationHeader()
    {
        /* Check if we have an authorization header */
        if (!isset(self::$headers['Authorization'])) {
            /* If not, return false */
            $auth_type = null;
            $auth_value = null;
        } else {
            /* Explode the header by a space and get the first value, make sure we remove all whitespace */
            $auth_array = explode(' ', self::$headers['Authorization']);
            $auth_type = trim($auth_array[0]);
            $auth_value = trim($auth_array[1]);
        }

        /* Set the value to the variable */
        self::$authenticationType = empty($auth_type) ? null : strtolower($auth_type);
        self::$authenticationValue = empty($auth_value) ? null : $auth_value;

        switch (self::$authenticationType) {
            case 'basic':
                self::$authenticationProvided = true;
                self::$authenticationValue = [
                    'type' => self::$authenticationType,
                    'token' => self::$authenticationValue,
                    'decoded' => base64_decode(self::$authenticationValue),
                    'username' => $_SERVER['PHP_AUTH_USER'],
                    'password' => $_SERVER['PHP_AUTH_PW'],
                ];
                break;

            case 'bearer':
                self::$authenticationProvided = true;
                self::$authenticationValue = [
                    'type' => self::$authenticationType,
                    'token' => self::$authenticationValue,
                ];
                break;

            case null:
                break;
        }
    }

    /**
     * Getters
     */

    public static function tokenized(): array
    {
        /* First check if we already parsed the request */
        if (!self::$isParsed) {
            /* if so, return the value */
            self::parse();
        }

        return self::$request;
    }

    public static function getHeaders(): array
    {
        /* First check if we already parsed the request */
        if (!self::$isParsed) {
            /* if so, return the value */
            self::parse();
        }

        return self::$headers;
    }

    /**
     * @param string $headerType
     */
    public static function getHeader(string $headerType)
    {
        /* First check if we already parsed the request */
        if (!self::$isParsed) {
            /* if so, return the value */
            self::parse();
        }

        return (isset(self::$headers[$headerType]) && !empty(self::$headers[$headerType])) ? self::$headers[$headerType] : false;
    }

    /**
     * @param string $name
     * @param $if_not_value
     */
    public function get(string $name, $if_not_value = null)
    {
        return isset(self::$query[$name]) ? self::$query[$name] : $if_not_value;
    }

    public static function hasAuthorization(): bool
    {
        /* First check if we already parsed the request */
        if (!self::$isParsed) {
            /* if so, return the value */
            self::parse();
        }

        return self::$authenticationProvided;
    }

    public static function getAuthenticationType()
    {
        /* First check if we already parsed the request */
        if (!self::$isParsed) {
            /* if so, return the value */
            self::parse();
        }

        return self::$authenticationType;
    }

    public static function getAuthenticationDetails()
    {
        /* First check if we already parsed the request */
        if (!self::$isParsed) {
            /* if so, return the value */
            self::parse();
        }

        return self::$authenticationValue;
    }

}
