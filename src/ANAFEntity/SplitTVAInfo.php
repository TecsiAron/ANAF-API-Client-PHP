<?php

namespace EdituraEDU\ANAF\ANAFEntity;

/**
 * Represents the split TVA(broken down tax payment?) information about a company based on the ANAF API response structure
 */
class SplitTVAInfo
{
    public string $dataInceputSplitTVA="";
    public string $dataAnulareSplitTVA="";
    public bool $statusSplitTVA=false;
    /**
     * Similar to @see Entity::CreateFromParsed
     * @param \stdClass $parsedData
     * @return SplitTVAInfo
     */
    public static function CreateFromParsed(\stdClass $parsedData):SplitTVAInfo
    {
        $splitTVAInfo = new SplitTVAInfo();
        $splitTVAInfo->dataInceputSplitTVA = $parsedData->dataInceputSplitTVA ?? "";
        $splitTVAInfo->dataAnulareSplitTVA = $parsedData->dataAnulareSplitTVA??"";
        $splitTVAInfo->statusSplitTVA = $parsedData->statusSplitTVA??false;
        return $splitTVAInfo;
    }
}