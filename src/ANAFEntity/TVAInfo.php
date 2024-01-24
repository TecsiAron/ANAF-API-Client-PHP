<?php

namespace EdituraEDU\ANAF\ANAFEntity;

use stdClass;

class TVAInfo
{
    public bool $scpTVA = false;
    public array $perioade_TVA=[];

    public static function CreateFromParsed(stdClass $parsedData):TVAInfo
    {
        $tvaInfo = new TVAInfo();
        $tvaInfo->scpTVA = $parsedData->scpTVA??false;
        $tvaInfo->perioade_TVA = $parsedData->perioade_TVA??[];
        return $tvaInfo;
    }
}