<?php

namespace EdituraEDU\ANAF\ANAFEntity;

use stdClass;

/**
 * Represents the split TVA(broken down tax payment?) information about a company based on the ANAF API response structure
 */
class SplitTVAInfo
{
    public string $dataInceputSplitTVA = "";
    public string $dataAnulareSplitTVA = "";
    public bool $statusSplitTVA = false;

    /**
     * Similar to @param stdClass $parsedData
     * @return SplitTVAInfo
     * @see Entity::CreateFromParsed
     */
    public static function CreateFromParsed(stdClass $parsedData): SplitTVAInfo
    {
        $splitTVAInfo = new SplitTVAInfo();
        $splitTVAInfo->dataInceputSplitTVA = $parsedData->dataInceputSplitTVA ?? "";
        $splitTVAInfo->dataAnulareSplitTVA = $parsedData->dataAnulareSplitTVA ?? "";
        $splitTVAInfo->statusSplitTVA = $parsedData->statusSplitTVA ?? false;
        return $splitTVAInfo;
    }
}