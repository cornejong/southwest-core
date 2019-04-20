<?php

namespace SouthCoast\SouthWest\Core;

use SouthCoast\Helpers\Env;

class Session
{
    const SETTINGS = [
        'name' => 'SOUTHWEST',
        'directory' => 'sessions',
    ];

    public static function start()
    {
        $session_path = Env::runtime_dir() . '/' . self::SETTINGS['directory'];

        self::ensureSessionDirecotry($session_path);

        session_name(Session::SETTINGS['name']);
        session_save_path($session_path);

        session_cache_limiter(Env::isDev() ? 'nocache' : 'public');

        session_start();

        session_gc();
    }

    public static function ensureSessionDirecotry($path)
    {
        if (!file_exists($path)) {
            mkdir($path);
        }
    }

    public function ID(string $id = null)
    {
        return session_id($id);
    }

    public static function get(string $name)
    {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }

    public static function set(string $name, $value)
    {
        $_SESSION[$name] = $value;
    }
}
