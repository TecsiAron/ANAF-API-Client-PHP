<?php

namespace EdituraEDU\ANAF\Callback;

interface ILogCallback
{
    public function Log(string $Message, ?\Throwable $ex): void;
}