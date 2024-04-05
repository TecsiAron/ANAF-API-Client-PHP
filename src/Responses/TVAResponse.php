<?php

namespace EdituraEDU\ANAF\Responses;
/**
 * Represents the response structure for @see \EdituraEDU\ANAF\ANAFAPIClient::CheckTVAStatus()
 */
class TVAResponse extends EntityResponse
{
    public bool|null $IsTVARegistered = null;

    public function Parse(): void
    {
        parent::Parse();
        if ($this->IsSuccess())
            $this->IsTVARegistered = $this->Entity->inregistrare_scop_Tva->scpTVA;

    }
}