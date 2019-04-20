<?php

namespace SouthCoast\SouthWest\Base;

class RedirectResponse
{
    public function __construct(string $location, string $method, array $headers, string $body = null)
    {
        # code...

        if (!is_null($body)) {
            $this->buildPostDataObject();
        }
    }

    public function buildPostDataObject()
    {
    }
}
