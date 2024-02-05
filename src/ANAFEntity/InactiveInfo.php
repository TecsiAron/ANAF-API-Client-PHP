<?php
namespace EdituraEDU\ANAF\ANAFEntity;

/**
 * Represents the inactive information about a company based on the ANAF API response structure
 */
class InactiveInfo
{
    public string $dataInactivare="";
    public string $dataReactivare="";
    public string $dataPublicare="";
    public string $dataRadiere="";
    public bool $statusInactivi=false;
    /**
     * Similar to @see Entity::CreateFromParsed
     * @param \stdClass $parsedData
     * @return InactiveInfo
     */
    public static function CreateFromParsed(\stdClass $parsedData):InactiveInfo
    {
        $inactiveInfo = new InactiveInfo();
        $inactiveInfo->dataInactivare = $parsedData->dataInactivare ?? "";
        $inactiveInfo->dataReactivare = $parsedData->dataReactivare??"";
        $inactiveInfo->dataPublicare = $parsedData->dataPublicare??"";
        $inactiveInfo->dataRadiere = $parsedData->dataRadiere??"";
        $inactiveInfo->statusInactivi = $parsedData->statusInactivi??"";
        return $inactiveInfo;
    }
}