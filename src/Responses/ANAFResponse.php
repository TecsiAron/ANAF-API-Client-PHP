<?php

namespace EdituraEDU\ANAF\Responses;

use InvalidArgumentException;
use Throwable;

/**
 * Represents the base structure for some ANAF API responses
 * @TODO make *Response classes extend this class
 */
abstract class ANAFResponse
{
    public Throwable|null $LastError = null;
    public string|null $rawResspone = null;

    public abstract function Parse(): bool;

    public function HasError(): bool
    {
        return $this->LastError != null;
    }

    /**
     * Utility method for implementers to create an error
     * @param string $message
     * @param int $code
     * @return void
     */
    protected function CreateError(string $message, int $code= ANAFException::UNKNOWN_ERROR): void
    {
        $this->LastError = new ANAFException($message);
    }
}