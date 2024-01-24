<?php
use EdituraEDU\ANAF\ANAFAPIClient;
$Logger= function (string $message)
{
	Logger::getInstance()->WriteLog("orders", Logger::ERROR, $message, true);
};
$anaf = new ANAFAPIClient(ANAF_OAUTH,false, $Logger);
var_dump($anaf->GetEntity("RO12345678"));