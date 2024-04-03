<?php

namespace EdituraEDU\ANAF\Responses;

use InvalidArgumentException;
use Throwable;

/**
 * Represents the base structure for some ANAF API responses
 * @TODO make *Response classes extend this class
 * @property bool $success
 * @property string $message
 */
abstract class ANAFResponse
{
    /**
     * @deprecated will be removed on next major version along with magic setter ang getter
     * @var bool|null
     */
    private bool|null $success = null;
    /**
     * @deprecated will be removed on next major version along with magic setter ang getter
     * @var bool|null
     */
    private string|null $message = null;
    public Throwable|null $LastError = null;
    public string|null $rawResspone = null;
    public string $LastParseError = "";

    public abstract function Parse(): bool;

    /**
     * @param $name
     * @return mixed
     * @deprecated will be removed on next major version
     */
    public function __get($name): mixed
    {
        if ($name == "success") {
            return $this->IsSuccess();
        }
        if ($name == "message") {
            return $this->IsSuccess() ? "" : $this->LastError->getMessage();
        }
        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     * @deprecated will be removed on next major version
     */
    public function __set(string $name, mixed $value): void
    {
        if ($name == "success") {
            if ($value === true) {
                $this->LastError = null;
            } else if ($value === false) {
                $this->LastError = new ANAFException();
            } else {
                throw new InvalidArgumentException("Invalid value for success property, must be bool");
            }
        }
        if ($name == "message") {
            if (is_string($value)) {
                if ($this->IsSuccess()) {
                    $this->LastError = new ANAFException($value);
                } else {
                    $this->LastError = new ANAFException($value, $this->LastError->getCode(), $this->LastError->getPrevious());
                }
            } else {
                throw new InvalidArgumentException("Invalid value for message property, must be string");
            }
        }
    }

    public function IsSuccess(): bool
    {
        return $this->LastError === null;
    }
}