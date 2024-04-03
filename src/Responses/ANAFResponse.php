<?php

namespace EdituraEDU\ANAF\Responses;

use InvalidArgumentException;
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

    protected function CommonParseJSON(string|null $response): stdClass|array|null
    {
        if(empty($response))
        {
            $this->CreateError("No response to parse", ANAFException::EMPTY_RAW_RESPONSE);
            return null;
        }
        $parsed = json_decode($response);
        $parseError = json_last_error();
        if ($parseError !== JSON_ERROR_NONE) {
            $this->CreateError("JSON parse error:" . $parseError, ANAFException::JSON_PARSE_ERROR);
            return null;
        }
        return $parsed;
    }
}