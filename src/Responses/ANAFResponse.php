<?php

namespace EdituraEDU\ANAF\Responses;

use stdClass;
use Throwable;

/**
 * Represents the base structure for some ANAF API responses
 * @TODO make *Response classes extend this class
 */
abstract class ANAFResponse
{
    public Throwable|null $LastError = null;
    public string|null $rawResponse = null;

    public abstract function Parse(): bool;

    public static abstract function CreateError(Throwable $error): ANAFResponse;

    public function HasError(): bool
    {
        return $this->LastError != null;
    }

    /**
     * Utility method for implementers to create an error
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @return void
     */
    protected function InternalCreateError(string $message, int $code= ANAFException::UNKNOWN_ERROR, ?Throwable $previous=null): void
    {
        $this->LastError = new ANAFException($message, $code, $previous);
    }

    protected function CommonParseJSON(string|null $response): stdClass|array|null
    {
        if(empty($response))
        {
            $this->InternalCreateError("No response to parse", ANAFException::EMPTY_RAW_RESPONSE);
            return null;
        }
        $parsed = json_decode($response);
        $parseError = json_last_error();
        if ($parseError !== JSON_ERROR_NONE) {
            $this->InternalCreateError("JSON parse error:" . $parseError, ANAFException::JSON_PARSE_ERROR);
            return null;
        }
        return $parsed;
    }
}