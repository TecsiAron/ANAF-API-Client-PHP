<?php

namespace EdituraEDU\ANAF\ANAFEntity;

use stdClass;

/**
 * Represents the TVA information about a company based on the ANAF API response structure
 */
class TVAInfo
{
    public bool $scpTVA = false;
    public array $perioade_TVA=[];
    /**
     * Similar to @see Entity::CreateFromParsed
     * @param stdClass $parsedData
     * @return TVAInfo
     */
    public static function CreateFromParsed(stdClass $parsedData):TVAInfo
    {
        $tvaInfo = new TVAInfo();
        $tvaInfo->scpTVA = $parsedData->scpTVA??false;
        $tvaInfo->perioade_TVA = $parsedData->perioade_TVA??[];
        return $tvaInfo;
    }
}