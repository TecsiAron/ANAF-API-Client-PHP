<?php

namespace EdituraEDU\ANAF\Responses;
/**
 * Represents the response structure for @see \EdituraEDU\ANAF\ANAFAPIClient::CheckTVAStatus()
 */
class TVAResponse extends EntityResponse
{
    public bool|null $IsTVARegistered=null;
    public function Parse(): bool
    {
        if(!parent::Parse())
        {
            return  false;
        }
        $this->IsTVARegistered=$this->Entity->inregistrare_scop_Tva->scpTVA;
        return  true;
    }
}