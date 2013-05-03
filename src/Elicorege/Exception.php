<?php
namespace Elicorege;

class Exception extends \Exception
{
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        $message = sprintf("!!! ERROR: %s !!!", $message);
        parent::__construct($message, $code, $previous);
    }
}

