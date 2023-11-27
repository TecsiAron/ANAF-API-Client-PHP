<?php

namespace EdituraEDU\Admin\ANAF\ANAFEntity;

use stdClass;

class GeneralInfo
{
    public string $cui = "";
    public string $data= "";
    public string $denumire = "";
    public string $adresa = "";
    public string $nrRegCom = "";
    public string $telefon = "";
    public string $fax = "";
    public string $codPostal ="";
    public string $act="";
    public string $stare_inregistrare="";
    public string $cod_CAEN="";
    public string $iban="";
    public bool $statusRO_e_Factura=false;
    public string $organFiscalCompetent="";
    public string $forma_de_proprietate="";
    public string $forma_organizare="";
    public string $forma_juridica="";

    public static function CreateFromParsed(stdClass $parsed):?GeneralInfo
    {
        try
        {
            $parsed=json_decode(json_encode($parsed), true);
            $properties=["cui", "data", "denumire", "adresa", "nrRegCom", "telefon", "fax", "codPostal", "act", "stare_inregistrare", "cod_CAEN", "iban", "statusRO_e_Factura", "organFiscalCompetent", "forma_de_proprietate", "forma_organizare", "forma_juridica"];
            $result = new GeneralInfo();
            for($i=0;$i<count($properties);$i++)
            {
                $property=$properties[$i];
                if(isset($parsed[$property]))
                {
                    $result->$property = $parsed[$property];
                }
            }
            return $result;
        }
        catch (\Throwable $th)
        {
            error_log($th->getMessage());
            error_log($th->getTraceAsString());
            return null;
        }
    }
}