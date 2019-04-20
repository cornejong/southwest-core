<?php

namespace SouthCoast\SouthWest\Helpers;

use SouthCoast\Helpers\Validate;
use SouthCoast\SouthWest\Core\Config;

class Url
{
    private static $App;

    /**
     * Builds a url link from app web config and supplied data
     * @method  Url::link()
     * @param   array   $params     |   ['some/page', 'optionalParam' => 'value']
     * @param   string  $base       |   The URL Base
     * @return  string
     */

    public static function link(array $location, $base = 'http')
    {
        extract(self::extractEndpointAndParams($location), EXTR_OVERWRITE);

        return self::base($base) . '/' . $endpoint . self::buildQuery($params);
    }

    /**
     * Builds App base url
     * @method  Url::base()
     * @param   string  $base       |   The URL Base
     * @return  string
     */

    public static function base($base = 'http')
    {
        if (!in_array($base, Config::get('app.web.method'))) {
            $base = Config::get('app.web.method')['default'];
        }

        return $base . '://' . Config::get('app.web.host') . ((Config::get('app.web.port') == null) ? '' : ':' . Config::get('app.web.port')) . ((Config::get('app.web.base_path') === '/') ? '' : '/' . Config::get('app.web.base_path'));
    }

    /**
     * Builds Link to Image
     * @method  Url::image()
     * @param   array   $params         |   Array of parameters ['jpeg', 'name']
     * @param   string  $base           |   The URL Base
     * @return  string
     */

    public static function image(array $params, $base = 'http')
    {
        return self::base($base) . '/assets/image/' . $params[0] . '/' . $params[1];
    }

    public static function isValid(string $url): bool
    {
        return Validate::url($url) ? true : false;
    }

    public static function extractEndpointAndParams(array $params): array
    {
        $endpoint = $params[0];
        unset($params[0]);
        return ['endpoint' => $endpoint, 'params' => $params];
    }

    public static function buildQuery(array $params): string
    {
        if (empty($params)) {
            return '';
        }

        return '?' . http_build_query($params);
    }

}
