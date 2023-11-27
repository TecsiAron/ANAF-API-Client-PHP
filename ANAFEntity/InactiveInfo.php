<?php
namespace EdituraEDU\Admin\ANAF\ANAFEntity;

class InactiveInfo
{
    public string $dataInactivare="";
    public string $dataReactivare="";
    public string $dataPublicare="";
    public string $dataRadiere="";
    public bool $statusInactivi=false;

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