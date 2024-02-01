<?php
use EdituraEDU\ANAF\ANAFAPIClient;
$Logger= function (string $message)
{
    echo $message;
};
$anaf = new ANAFAPIClient(ANAF_OAUTH,false, $Logger);
var_dump($anaf->GetEntity("RO12345678"));