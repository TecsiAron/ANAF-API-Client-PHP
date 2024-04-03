<?php

namespace EdituraEDU\ANAF\Responses;

use EdituraEDU\ANAF\ANAFEntity\Entity;
use Throwable;

/**
 * Represents the response structure for @see \EdituraEDU\ANAF\ANAFAPIClient::GetEntity
 */
class EntityResponse extends ANAFResponse
{
    public ?Entity $Entity = null;

    public function Parse(): bool
    {
        if ($this->rawResspone === null) {
            $this->CreateError("No response to parse", ANAFException::EMPTY_RAW_RESPONSE);
            return false;
        }
        try {
            $parsed = json_decode($this->rawResspone);
            $parseError = json_last_error();
            if ($parseError !== JSON_ERROR_NONE) {
                $this->CreateError("JSON parse error:" . $parseError, ANAFException::JSON_PARSE_ERROR);
                return false;
            }
        } catch (Throwable $ex) {
            $this->CreateError($ex->getMessage(), ANAFException::JSON_UNKNOWN_ERROR);
            return false;
        }
        if (!isset($parsed->found) || !is_countable($parsed->found)) {
            $this->CreateError("Missing/invalid found array, bad response structure?", ANAFException::UNEXPECTED_RESPONSE_STRUCTURE);
            return false;
        }
        if (sizeof($parsed->found) == 0) {
            $this->CreateError("CUI invalid (nu s-a regasit in baza de date ANAF)!", ANAFException::RESULT_NOT_FOUND);
            return true;
        }
        if (sizeof($parsed->found) != 1) {
            $this->LastError = new ANAFException("Too many results", ANAFException::TOO_MANY_RESULTS);
            return false;
        }
        if (!isset($parsed->found[0]->date_generale) || !isset($parsed->found[0]->inregistrare_scop_Tva)) {
            $this->CreateError("Missing/invalid date_generale or inregistrare_scop_Tva, bad response structure?", ANAFException::INCOMPLETE_RESPONSE);
            return false;
        }
        if (!isset($parsed->found[0]->inregistrare_scop_Tva->scpTVA) || !is_bool($parsed->found[0]->inregistrare_scop_Tva->scpTVA)) {
            $this->CreateError("Missing/invalid scpTVA, bad response structure?", ANAFException::INCOMPLETE_RESPONSE);
            return false;
        }
        $this->Entity = Entity::CreateFromParsed($parsed->found[0]);
        return true;
    }
}