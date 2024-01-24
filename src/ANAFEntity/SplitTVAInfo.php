<?php

namespace EdituraEDU\ANAF\ANAFEntity;

class SplitTVAInfo
{
    public string $dataInceputSplitTVA="";
    public string $dataAnulareSplitTVA="";
    public bool $statusSplitTVA=false;

    public static function CreateFromParsed(\stdClass $parsedData):SplitTVAInfo
    {
        $splitTVAInfo = new SplitTVAInfo();
        $splitTVAInfo->dataInceputSplitTVA = $parsedData->dataInceputSplitTVA ?? "";
        $splitTVAInfo->dataAnulareSplitTVA = $parsedData->dataAnulareSplitTVA??"";
        $splitTVAInfo->statusSplitTVA = $parsedData->statusSplitTVA??false;
        return $splitTVAInfo;
    }
}