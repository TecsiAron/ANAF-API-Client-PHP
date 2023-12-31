<?php

namespace EdituraEDU\Admin\ANAF\Responses;

use EdituraEDU\Admin\ANAF\ANAFEntity\Entity;

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