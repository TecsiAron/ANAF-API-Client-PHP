<?php

namespace EdituraEDU\ANAF\Responses;

use Exception;
use Throwable;

class ANAFException extends  Exception
{
    public function __construct(string $message, int $code, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}