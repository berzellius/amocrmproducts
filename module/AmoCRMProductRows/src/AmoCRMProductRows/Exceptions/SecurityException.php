<?php

/**
 * Created by PhpStorm.
 * User: berz
 * Date: 23.01.2017
 * Time: 22:46
 */
namespace AmoCRMProductRows\Exceptions;

use Exception;

class SecurityException extends Exception
{
    function __construct($message = "security check failed", $code = 403, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}