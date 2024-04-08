<?php

namespace EdituraEDU\ANAF\ANAFEntity;
/**
 * Represents the comprehensive response structure for a company/institution from the ANAF API
 */
class Entity
{
    /**
     * @var GeneralInfo|null General information about the company nullable only for error handling!
     * It should never be null in a valid response!
     */
    public ?GeneralInfo $date_generale;
    /**
     * @var TVAInfo|null TVA information about the company, nullable if the company was not registered for TVA at the searched period
     */
    public ?TVAInfo $inregistrare_scop_Tva;
    /**
     * @var RTVAInfo|null RTVA (taxable on payment?) information about the company, nullable if the company was not registered for RTVA at the searched period
     */
    public ?RTVAInfo $inregistrare_RTVAI;
    /**
     * @var InactiveInfo|null Inactive information about the company, nullable if the company was not inactive for the searched period
     */
    public ?InactiveInfo $stare_inactiv;
    /**
     * @var SplitTVAInfo|null Split TVA(broken down tax payment?) information about the company, nullable if the company was not registered for split TVA at the searched period
     */
    public ?SplitTVAInfo $inregistrare_SplitTVA;
    /**
     * @var Address|null The company's registered office address, nullable if the address is not set
     */
    public ?Address $adresa_sediu_social;
    /**
     * @var Address|null The company's fiscal domicile address, nullable if the address is not set
     */
    public ?Address $adresa_domiciliu_fiscal;

    /**
     * Used to convert the parsed data (json) from the ANAF API to an Entity object
     * @param \stdClass $parsedData
     * @return Entity
     */
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

    /**
     * Try to extract Bucharest's sector from the address
     * Will return null if the address is not set or if the address does not contain a sector.
     * (Looks for the word "sector" followed by a digit, case-insensitive, surrounded by spaces or punctuation)
     * @return int|null
     */
    public function GetSector(): ?int
    {
        if ($this->date_generale == null) {
            return null;
        }

        $toSearch = $this->date_generale->adresa;
        if (empty($toSearch)) {
            return null;
        }

        $toSearch = strtolower($toSearch);

        if (preg_match('/\bsector\b\s*(\d)(?=\s|\p{P}|\z)/u', $toSearch, $matches)) {
            return (int)$matches[1]; // Cast the captured digit to an integer and return it
        }
        return null;
    }
}
