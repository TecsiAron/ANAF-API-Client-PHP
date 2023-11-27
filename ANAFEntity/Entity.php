<?php

namespace EdituraEDU\Admin\ANAF\ANAFEntity;
class Entity
{
    public ?GeneralInfo $date_generale;
    public ?TVAInfo $inregistrare_scop_Tva;
    public ?RTVAInfo $inregistrare_RTVAI;
    public ?InactiveInfo $stare_inactiv;
    public ?SplitTVAInfo $inregistrare_SplitTVA;
    public ?Address $adresa_sediu_social;
    public ?Address $adresa_domiciliu_fiscal;

    public static function CreateFromParsed(\stdClass $parsedData): Entity
    {
        $entity = new Entity();
        $entity->date_generale = isset($parsedData->date_generale) ? GeneralInfo::CreateFromParsed($parsedData->date_generale) : null;
        $entity->inregistrare_scop_Tva = isset($parsedData->inregistrare_scop_Tva) ? TVAInfo::CreateFromParsed($parsedData->inregistrare_scop_Tva) : null;
        $entity->inregistrare_RTVAI = isset($parsedData->inregistrare_RTVAI) ? RTVAInfo::CreateFromParsed($parsedData->inregistrare_RTVAI) : null;
        $entity->stare_inactiv = isset($parsedData->stare_inactiv) ? InactiveInfo::CreateFromParsed($parsedData->stare_inactiv) : null;
        $entity->inregistrare_SplitTVA = isset($parsedData->inregistrare_SplitTVA) ? SplitTVAInfo::CreateFromParsed($parsedData->inregistrare_SplitTVA) : null;
        $entity->adresa_sediu_social = isset($parsedData->adresa_sediu_social) ? Address::CreateFromParsed($parsedData->adresa_sediu_social) : null;
        $entity->adresa_domiciliu_fiscal = isset($parsedData->adresa_domiciliu_fiscal) ? Address::CreateFromParsed($parsedData->adresa_domiciliu_fiscal) : null;
        return $entity;
    }

    public function GetSector(): ?int
    {
        if ($this->date_generale == null)
        {
            return null;
        }

        $toSearch = $this->date_generale->adresa;
        if (empty($toSearch))
        {
            return null;
        }

        $toSearch = strtolower($toSearch);

        if (preg_match('/\bsector\b\s*(\d)(?=\s|\p{P}|\z)/u', $toSearch, $matches))
        {
            return (int)$matches[1]; // Cast the captured digit to an integer and return it
        }
        return null;
    }
}
