<?php

namespace SouthCoast\SouthWest\Base;

use ArrayAccess;
use SouthCoast\Helpers\Identifier;

class Cookie implements ArrayAccess
{
    const EXPIRES_HOUR = 60;
    const EXPIRES_DAY = 3600;

    protected $identifier = null;
    protected $name = null;
    protected $expires = null;
    protected $secure = true;
    protected $path = '/';
    protected $domain = null;
    protected $created = null;
    protected $data = [];

    //abstract public function __sleep();
    //abstract public function __wakeup();

    public function __construct(string $name)
    {
        $this->identifier = Identifier::newGuid();
        $this->created = time();
        $this->setName($name)->setExpires(Cookie::EXPIRES_DAY);

        return $this;
    }

    protected function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    public function setPath(string $path)
    {
        $this->path = $path;

        return $this;
    }

    public function setDomain(string $domain)
    {
        $this->domain = $domain;

        return $this;
    }

    public function setExpires(int $expires)
    {
        $this->expires = time() + $expires;

        return $this;
    }

    public function save()
    {
        setcookie($this->name, $this->hibernate(), $this->expires, $this->path, $this->domain, $this->secure);
    }

    final public function __invoke(string $name)
    {
        return (isset($this->data[$name])) ? $this->data[$name] : null;
    }

    final public function __clone()
    {
        $this->identifier = Identifier::newGuid();
    }

    final public function hibernate()
    {
        return serialize($this);
    }

    final public static function get(string $name)
    {
        return isset($_COOKIE[$name]) && @unserialize($_COOKIE[$name]) == !false ? unserialize($_COOKIE[$name]) : false;
    }

    public static function exists(string $name)
    {
        return isset($_COOKIE[$name]) ? true : false;
    }

    public function offsetSet($offset, $value)
    {
        if ($offset) {
            return ($this->data[$offset] = $value) ? true : false;
        } else {
            return ($this->data[] = $value) ? true : false;
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]) ? true : false;
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return ($this->offsetExists($offset)) ? $this->data[$offset] : null;
    }
}
