<?php

namespace SouthCoast\SouthWest\Core;

use ErrorException;

class ErrorHandler
{
    private static $log_to_file = false;
    private static $log_directory = '';

    public static function register()
    {
        set_exception_handler(__CLASS__ . '::ExceptionHandler');
        set_error_handler(__CLASS__ . '::ErrorHandler');

        self::$log_to_file = Config::get('app.errorhandling.log_to_file') ?? false;
        self::$log_directory = Config::get('app.errorhandling.log_file_directory') ?? '';
    }

    public static function registerCustomErrorHandler($callback)
    {
        # code...
    }

    public static function registerErrorLogger($callback)
    {
        # code...
    }

    public static function registerErrorService($callback)
    {
        # code...
    }

    public static function ExceptionHandler(\Throwable $th)
    {
        if (self::$log_to_file) {
            self::logExceptionToFile($th);
        }

        $responseType = Config::get('app.type');
        $errorResponse = Config::get($responseType . '.throwableResponseObject');
        $response = new $errorResponse($th);
        $response->send();
        exit;
    }

    public static function logExceptionToFile(\Throwable $th)
    {
        # code...
    }

    public static function ErrorHandler($err_severity, $err_msg, $err_file, $err_line, array $err_context = null)
    {
        if (0 === error_reporting()) {
            return false;
        }

        switch ($err_severity) {
            case E_ERROR:
                throw new ErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_WARNING:
                throw new WarningException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_PARSE:
                throw new ParseException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_NOTICE:
                throw new NoticeException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_CORE_ERROR:
                throw new CoreErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_CORE_WARNING:
                throw new CoreWarningException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_COMPILE_ERROR:
                throw new CompileErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_COMPILE_WARNING:
                throw new CoreWarningException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_USER_ERROR:
                throw new UserErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_USER_WARNING:
                throw new UserWarningException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_USER_NOTICE:
                throw new UserNoticeException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_STRICT:
                throw new StrictException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_RECOVERABLE_ERROR:
                throw new RecoverableErrorException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_DEPRECATED:
                throw new DeprecatedException($err_msg, 0, $err_severity, $err_file, $err_line);
            case E_USER_DEPRECATED:
                throw new UserDeprecatedException($err_msg, 0, $err_severity, $err_file, $err_line);
        }
    }
}

class WarningException extends ErrorException
{}
class ParseException extends ErrorException
{}
class NoticeException extends ErrorException
{}
class CoreErrorException extends ErrorException
{}
class CoreWarningException extends ErrorException
{}
class CompileErrorException extends ErrorException
{}
class CompileWarningException extends ErrorException
{}
class UserErrorException extends ErrorException
{}
class UserWarningException extends ErrorException
{}
class UserNoticeException extends ErrorException
{}
class StrictException extends ErrorException
{}
class RecoverableErrorException extends ErrorException
{}
class DeprecatedException extends ErrorException
{}
class UserDeprecatedException extends ErrorException
{}
