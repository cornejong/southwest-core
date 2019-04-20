<?php

namespace SouthCoast\SouthWest\Component\Rest;

use SouthCoast\Helpers\Validate;

class Request
{
    private static $allow_redirect = true;
    private static $max_redirects = 10;
    private static $time_out = 30;
    private static $default_headers = [];

    public static function Get(string $url, array $headers = [])
    {
        return Request::go('get', $url, $headers);
    }

    public static function Post(string $url, string $body, array $headers = [])
    {
        return Request::go('post', $url, $headers, $body);
    }

    public static function Put(string $url, string $body, array $headers = [])
    {
        return Request::go('put', $url, $headers, $body);
    }

    public static function Patch(string $url, string $body, array $headers = [])
    {
        return Request::go('patch', $url, $headers, $body);
    }

    public static function Delete(string $url, array $headers = [])
    {
        return Request::go('delete', $url, $headers);
    }

    public static function Option(string $url, array $headers = [])
    {
        return Request::go('option', $url, $headers);
    }

    private static function go(string $method, string $url, array $headers, string $body = null)
    {

        if (!Validate::url($url)) {
            throw new ValidationError(ValidationError::URL, $url, 'Invalid Url!');
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => strtoupper(trim($method)),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => self::$max_redirects,
            CURLOPT_TIMEOUT => self::$time_out,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_HTTPHEADER => array_merge($headers, self::$default_headers),
        ]);

        if (self::$allow_redirect) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        }

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            /* TODO: return new rest response Error */
            return "cURL Error: " . $err;
        } else {
            /* TODO: return new rest response */
            return $response;
        }
    }

}
