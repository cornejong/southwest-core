<?php

namespace SouthCoast\SouthWest\Component\OauthProvider;

use SouthCoast\Helpers\ArrayHelper;
use SouthCoast\Helpers\Validate;

class OauthProvider
{

    protected $auth_url;
    protected $token_url;
    protected $callback_url;

    public function __construct(array $setup)
    {
        if (!ArrayHelper::requiredPramatersAreSet(self::REQUIRED_PARAMATERS, $setup, $missing, true)) {
            throw new OathError(OauthProvider::MISSING_PARAMETERS, $missing);
        }

        foreach ($setup as $data_type => $value) {
            call_user_func([$this, 'set' . ucfirst($data_type)], $value);
        }
    }

    public function authenticateRedirect()
    {
        $query = [];
    }

    public function authenticate(Type $var = null)
    {
        # code...
    }

    public function setAuthUrl(string $url)
    {
        if (!Validate::url($url)) {
            $url = Validate::urlSanitizer($url);
            if (!Validate::url($url)) {
                throw new ValidationError(ValidationError::URL, $url, 'Invalid Auth Url!');
            }
        }

        $this->auth_url = $url;
    }

    public function setTokenhUrl(string $url)
    {
        if (!Validate::url($url)) {
            $url = Validate::urlSanitizer($url);
            if (!Validate::url($url)) {
                throw new ValidationError(ValidationError::URL, $url, 'Invalid Tokeb Url!');
            }
        }

        $this->token_url = $url;
    }

    public function setCallbackhUrl(string $url)
    {
        if (!Validate::url($url)) {
            $url = Validate::urlSanitizer($url);
            if (!Validate::url($url)) {
                throw new ValidationError(ValidationError::URL, $url, 'Invalid Callback Url!');
            }
        }

        $this->callback_url = $url;
    }
}
