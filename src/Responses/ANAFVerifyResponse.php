<?php

namespace EdituraEDU\ANAF\Responses;
/**
 * Represents the response structure for @see \EdituraEDU\ANAF\ANAFAPIClient::VerifyXML
 */
class ANAFVerifyResponse
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
        if(strtolower($this->stare)=="ok")
        {
            return true;
        }
        return false;
    }
    /**
     * Similar to @see Entity::CreateFromParsed
     * @param \stdClass $parsed
     * @return ANAFVerifyResponse
     */
    public static function CreateFromParsed($parsed): ANAFVerifyResponse
    {
        $response = new ANAFVerifyResponse();
        $response->stare = $parsed->stare;
        if(!isset($parsed->Messages) || $parsed->Messages==null)
        {
            $parsed->Messages = [];
        }
        else
        {
            $response->Messages = $parsed->Messages;
        }
        $response->trace_id = $parsed->trace_id;
        return $response;
    }
}