<?php

namespace EdituraEDU\ANAF\Responses;

/**
 * Represents the base structure for some ANAF API responses
 * @TODO make *Response classes extend this class
 */
abstract class ANAFAPIResponse
{
    public bool|null $success=null;
    public string|null $message=null;
    public string|null $rawResspone=null;
    public string $LastParseError="";
    public abstract function Parse():bool;
}