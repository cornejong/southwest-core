<?php

namespace SouthCoast\SouthWest\Throwables;

use SouthCoast\SouthWest\Core\Request;

class HttpError extends \Exception
{
    const CUSTOM = 0;
    const NOT_FOUND = 1;
    const UNAUTHORIZED = 2;
    const INSUFFICIENT = 3;
    const GONE = 3;
    const INTERNAL_ERROR = 4;
    const NO_CONTENT = 5;
    const UPGRADE = 6;
    const MISSING_PARAMETERS = 7;
    const UNKNOWN_USER = 8;
    const INCORRECT_PASSCODE = 9;
    const ACCOUNT_BLOCKED = 10;
    const METHOD_NOT_ALLOWED = 11;
    const INVALID_CONTENT_TYPE = 12;

    const AAAAAH = 9999999;

    /**
     * @var mixed
     */
    public $error_code = self::AAAAAH;

    /**
     * @var mixed
     */
    public $status_code;

    /**
     * @var mixed
     */
    protected $error_additional;

    /**
     * @param $error_code
     * @param $data
     */
    public function __construct($error_code = self::AAAAAH, ...$data)
    {
        $this->error_code = $error_code;
        $this->error_additional = $data;
    }

    /**
     * @return mixed
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * @param int $error_code
     */
    public function getBody(): array
    {
        /* Set the default values for the required parameters */
        /* The Status Code of the response */
        $status_code = 500;
        /* The short version of the error */
        $short = 'UNKNOWN';
        /* The full error message */
        $full = 'SOMETHING WENT WRONG! !%#&?';
        /* Additional Information */
        $additional = $this->error_additional;
        /* If 'more_info' needs to be added */
        $moreInfo = true;

        switch ($this->error_code) {
            case HttpError::NOT_FOUND:
                $status_code = 404;
                $short = 'The endpoint could not be found!';
                $full = 'The chosen endpoint \'' . Request::$path . '\' could not be found in this application.';
                break;

            case HttpError::UNAUTHORIZED:
                $status_code = 401;
                $short = 'Authorization Required!';
                $full = 'The endpoint \'' . Request::$path . '\' is only accessible for authorized users. Please obtain a bearer token to access this resource.';
                break;

            case HttpError::INSUFFICIENT:
                $status_code = 403;
                $short = 'Insufficient Access Rights!';
                $full = 'Your current access token is outside the scope needed to access this resource.';
                break;

            case HttpError::GONE:
                $status_code = 410;
                $short = 'Oeps! This endpoint is gone!';
                $full = 'The requested endpoint no longer exists.';
                break;

            case HttpError::INTERNAL_ERROR:
                $status_code = 500;
                $short = 'The was an internal server error!';
                $full = 'An internal server error occurred. Please try again later. If the error keeps occurring please contact us.';
                break;

            case HttpError::NO_CONTENT:
                $status_code = 204;
                $short = 'There is no content to respond with!';
                $full = 'There is no content for the requested endpoint. If you\'re expecting content to be at this endpoint please contact us';
                break;

            case HttpError::UPGRADE:
                $status_code = 426;
                $short = 'Upgrade Required for this endpoint!';
                $full = 'This endpoint is not included in your subscription. Please upgrade your subscription to be able to use this functionality.';
                $additional = [
                    'current_subscription' => '',
                    'needed_subscription' => '',
                    'upgrade_url' => '',
                ];
                $moreInfo = false;
                break;

            case HttpError::MISSING_PARAMETERS:
                $status_code = 422;
                $short = 'Required parameter(s) missing!';
                $full = 'One or more required parameters are missing in your request. Check \'additional\' for the missing parameters.';
                $additional = ['missing' => $this->error_additional];
                break;

            case HttpError::UNKNOWN_USER:
                $status_code = 401;
                $short = 'Unknown User!';
                $full = 'The provided username is unknown.';
                break;

            case HttpError::INCORRECT_PASSCODE:
                $status_code = 401;
                $short = 'Incorrect Credentials!';
                $full = 'The provided [ username <-> passcode ] combination is invalid. Please try agian.';
                $additional = $this->error_additional;
                break;

            case HttpError::ACCOUNT_BLOCKED:
                $status_code = 401;
                $short = 'Account Blocked!';
                $full = 'The account used for authentication is currently blocked because you\'ve reached the number of authentication tries. Please contact us to re-activate.';
                break;

            case HttpError::METHOD_NOT_ALLOWED:
                $status_code = 405;
                $short = 'Request Method not Allowed!';
                $full = 'The request method \'' . $_SERVER['REQUEST_METHOD'] . '\' is nog allowed. Please Check \'additional\' for the accepted request methods.';
                $additional = ['accepted' => $this->error_additional];
                break;

            case HttpError::INVALID_CONTENT_TYPE:
                $status_code = 405;
                $short = 'Content-Type not Allowed!';
                $full = 'The content type \'' . $this->error_additional['provided'] . '\' is nog allowed. Please Check \'additional\' for the accepted request methods.';
                $additional = ['accepted' => $this->error_additional['accepted']];
                break;
        }

        /* Check if 'more_info' needs to be added */
        if ($moreInfo) {
            $additional['more_info'] = 'https://httpstatuses.com/' . $status_code;
        }

        /* Set the response code */
        $this->status_code = $status_code;

        /* Return the message */
        return [
            'code' => 'E_' . $this->error_code,
            'short' => $short,
            'full' => $full,
            'additional' => !empty($additional) ? $additional : null,
        ];
    }
}
