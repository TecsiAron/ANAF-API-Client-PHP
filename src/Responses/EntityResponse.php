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

    public function Parse(): void
    {
        try {
            $parsed = $this->CommonParseJSON($this->rawResponse);
            if ($parsed == null && !$this->HasError()) {
                $this->InternalCreateError("Internal error parsing response");
                return;
            }
        } catch (Throwable $ex) {
            $this->InternalCreateError($ex->getMessage(), ANAFException::JSON_UNKNOWN_ERROR, $this->LastError);
            return;
        }
        if (!isset($parsed->found) || !is_countable($parsed->found)) {
            $this->InternalCreateError("Missing/invalid found array, bad response structure?", ANAFException::UNEXPECTED_RESPONSE_STRUCTURE);
            return;
        }
        if (sizeof($parsed->found) == 0) {
            $this->InternalCreateError("CUI invalid (nu s-a regasit in baza de date ANAF)!", ANAFException::RESULT_NOT_FOUND);
            return;
        }
        if (sizeof($parsed->found) != 1) {
            $this->LastError = new ANAFException("Too many results", ANAFException::TOO_MANY_RESULTS);
            return;
        }
        if (!isset($parsed->found[0]->date_generale) || !isset($parsed->found[0]->inregistrare_scop_Tva)) {
            $this->InternalCreateError("Missing/invalid date_generale or inregistrare_scop_Tva, bad response structure?", ANAFException::INCOMPLETE_RESPONSE);
            return;
        }
        if (!isset($parsed->found[0]->inregistrare_scop_Tva->scpTVA) || !is_bool($parsed->found[0]->inregistrare_scop_Tva->scpTVA)) {
            $this->InternalCreateError("Missing/invalid scpTVA, bad response structure?", ANAFException::INCOMPLETE_RESPONSE);
            return;
        }
        $this->Entity = Entity::CreateFromParsed($parsed->found[0]);
    }

    public static function CreateError(Throwable $error): EntityResponse
    {
        $result = new EntityResponse();
        $result->LastError = $error;
        return $result;
    }
}