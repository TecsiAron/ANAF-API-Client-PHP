<?php

namespace EdituraEDU\ANAF\Responses;

abstract class ANAFAPIResponse
{
    public bool|null $success=null;
    public string|null $message=null;
    public string|null $rawResspone=null;
    public string $LastParseError="";
    public abstract function Parse():bool;
}