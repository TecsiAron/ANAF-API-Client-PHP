<?php

namespace EdituraEDU\ANAF\ANAFEntity;

use stdClass;

/**
 * Represents the inactive information about a company based on the ANAF API response structure
 */
class InactiveInfo
{
    public string $dataInactivare = "";
    public string $dataReactivare = "";
    public string $dataPublicare = "";
    public string $dataRadiere = "";
    public bool $statusInactivi = false;

    /**
     * Similar to @param stdClass $parsedData
     * @return InactiveInfo
     * @see Entity::CreateFromParsed
     */
    public static function CreateFromParsed(stdClass $parsedData): InactiveInfo
    {
        $inactiveInfo = new InactiveInfo();
        $inactiveInfo->dataInactivare = $parsedData->dataInactivare ?? "";
        $inactiveInfo->dataReactivare = $parsedData->dataReactivare ?? "";
        $inactiveInfo->dataPublicare = $parsedData->dataPublicare ?? "";
        $inactiveInfo->dataRadiere = $parsedData->dataRadiere ?? "";
        $inactiveInfo->statusInactivi = $parsedData->statusInactivi ?? false;
        return $inactiveInfo;
    }
}