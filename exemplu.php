<?php
use EdituraEDU\ANAF\ANAFAPIClient;
$Logger= function (string $message, ?Throwable $ex = null)
{
    echo $message;
	if($ex !== null)
	{
		echo $ex->getMessage();
	}
};
$anaf = new ANAFAPIClient(ANAF_OAUTH,false, $Logger);
var_dump($anaf->GetEntity("RO12345678"));