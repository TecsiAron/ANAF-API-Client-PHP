<?php

namespace EdituraEDU\ANAF\Responses;
/**
 * Used to properly mark deprecated members of ANAFResponse
 * Will be removed on next major version
 */
trait DeprecatedANAFResponseMembers
{
    /**
     * @deprecated will be removed on next major version
     * @var bool|null
     */
    public $success;
    /**
     * @deprecated will be removed on next major version
     * @var string|null
     */
    public $message;
}