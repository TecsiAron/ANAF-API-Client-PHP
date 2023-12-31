<?php

namespace EdituraEDU\Admin\ANAF\Responses;

use EdituraEDU\Admin\ANAF\ANAFEntity\Entity;

class EntityResponse extends ANAFAPIResponse
{
    public ?Entity $Entity=null;
    public function Parse(): bool
    {
        if($this->rawResspone===null)
        {
            $this->LastParseError = "No response to parse";
            return false;
        }
        try
        {
            $parsed = json_decode($this->rawResspone);
        }
        catch (\Throwable $ex)
        {
            $this->LastParseError = "Failed to parse json";
            return false;
        }
        if(!isset($parsed->found) || !is_countable($parsed->found))
        {
            $this->LastParseError = "Missing/invalid found array, bad response structure?";
            return false;
        }
        if(sizeof($parsed->found)==0)
        {
            $this->success = false;
            $this->message = "CUI invalid (Nu sa regasit in baza de date ANAF)!";
            return true;
        }
        if(sizeof($parsed->found)!=1)
        {
            $this->success = false;
            $this->message="Prea multe rezultate, CUI incomplet?";
            return true;
        }
        if(!isset($parsed->found[0]->date_generale) || !isset($parsed->found[0]->inregistrare_scop_Tva))
        {
            $this->LastParseError = "Missing/invalid date_generale or inregistrare_scop_Tva, bad response structure?";
            return false;
        }
        if(!isset($parsed->found[0]->inregistrare_scop_Tva->scpTVA) || !is_bool($parsed->found[0]->inregistrare_scop_Tva->scpTVA))
        {
            $this->LastParseError = "Missing/invalid scpTVA, bad response structure?";
            return false;
        }
        $this->Entity = Entity::CreateFromParsed($parsed->found[0]);
        return true;
    }
}