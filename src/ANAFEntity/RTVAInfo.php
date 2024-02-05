<?php
namespace EdituraEDU\ANAF\ANAFEntity;
/**
 * Represents the RTVA (taxable on payment?)  information about a company based on the ANAF API response structure
 */
class RTVAInfo
{
    public string $dataInceputTvaInc="";
    public string $dataSfarsitTvaInc="";
    public string $dataActualizareTvaInc="";
    public string $dataPublicareTvaInc="";
    public string $tipActTvaInc="";
    public bool $statusTvaIncasare=false;
    /**
     * Similar to @see Entity::CreateFromParsed
     * @param \stdClass $parsedData
     * @return RTVAInfo
     */
    public static function CreateFromParsed(\stdClass $parsedData):RTVAInfo
    {
        $rtvaInfo = new RTVAInfo();
        $rtvaInfo->dataInceputTvaInc = $parsedData->dataInceputTvaInc??"";
        $rtvaInfo->dataSfarsitTvaInc = $parsedData->dataSfarsitTvaInc??"";
        $rtvaInfo->dataActualizareTvaInc = $parsedData->dataActualizareTvaInc??"";
        $rtvaInfo->dataPublicareTvaInc = $parsedData->dataPublicareTvaInc??"";
        $rtvaInfo->tipActTvaInc = $parsedData->tipActTvaInc??"";
        $rtvaInfo->statusTvaIncasare = $parsedData->statusTvaIncasare??false;
        return $rtvaInfo;
    }
}