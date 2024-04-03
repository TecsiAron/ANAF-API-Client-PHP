<?php

namespace EdituraEDU\ANAF\Responses;

use Throwable;

/**
 * Represents the response structure for @see \EdituraEDU\ANAF\ANAFAPIClient::VerifyXML
 */
class ANAFVerifyResponse extends ANAFResponse
{
    public string $stare;
    public array $Messages;
    public string $trace_id;

    /**
     * Check if the response is OK
     * @return bool
     */
    public function IsOK(): bool
    {
        if ($this->HasError()) {
            return false;
        }
        if (strtolower($this->stare) == "ok") {
            return true;
        }
        return false;
    }

    /**
     * Similar to
     * @param mixed $parsed
     * @return void
     */
    private function CopyFromParsed(mixed $parsed): void
    {
        $this->stare = $parsed->stare;
        if (!isset($parsed->Messages) || $parsed->Messages == null) {
            $this->Messages = [];
        } else {
            $this->Messages = $parsed->Messages;
        }
        $this->trace_id = $parsed->trace_id;
    }


    public function Parse(): bool
    {
        try {
            $parsed = $this->CommonParseJSON($this->rawResponse);
            if ($parsed == null) {
                if (!$this->HasError()) {
                    $this->InternalCreateError("Internal error parsing response", ANAFException::UNKNOWN_ERROR);
                }
                return false;
            }
            $this->CopyFromParsed($parsed);
            return true;
        } catch (Throwable $ex) {
            $this->InternalCreateError($ex->getMessage(), ANAFException::JSON_UNKNOWN_ERROR, $this->LastError);
            return false;
        }
    }

    public static function Create($rawResponse): ANAFVerifyResponse
    {
        $response = new ANAFVerifyResponse();
        $response->rawResponse = $rawResponse;
        $response->Parse();
        return $response;
    }

    public static function CreateError(Throwable $ex): ANAFVerifyResponse
    {
        $response = new ANAFVerifyResponse();
        $response->LastError = $ex;
        return $response;
    }
}