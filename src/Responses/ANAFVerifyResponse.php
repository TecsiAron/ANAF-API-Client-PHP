<?php

namespace EdituraEDU\ANAF\Responses;

class ANAFVerifyResponse
{
    public string $stare;
    public array $Messages;
    public string $trace_id;


    public function IsOK(): bool
    {
        if(strtolower($this->stare)=="ok")
        {
            return true;
        }
        return false;
    }
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