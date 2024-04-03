<?php

namespace EdituraEDU\ANAF\Responses;

use Exception;
use Throwable;

class ANAFException extends  Exception
{
    const UNKNOWN_ERROR = 0;
    public function __construct(string $message="Unknown error", int $code = self::UNKNOWN_ERROR, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}