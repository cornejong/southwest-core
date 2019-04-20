<?php

namespace SouthCoast\SouthWest\Core;

use SouthCoast\Helpers\ArrayHelper;
use SouthCoast\Helpers\Env;

class Config
{
    protected static $config = [];

    public static function get(string $param)
    {
        return ArrayHelper::get($param, self::$config, true, true);
    }

    public static function load(string $path = null)
    {
        if (!is_null($path)) {
            $files = [$path];
        } else {
            $files = glob(Env::base_dir() . '/Config/*.php');
        }

        foreach ($files as $file) {
            self::$config[strtolower(basename($file, '.php'))] = require $file;
        }
    }
}
