<?php
namespace EdituraEDU\Admin\ANAF\ANAFEntity;
use stdClass;

class Address
{
    public string $denumire_Strada="";
    public string $numar_Strada="";

    public string $denumire_Localitate="";
    public string $cod_Localitate="";
    public string $denumire_Judet="";
    public string $cod_Judet="";
    public string $cod_JudetAuto="";
    public string $tara="";
    public string $detalii_Adresa="";
    public string $cod_Postal="";

    public static function CreateFromParsed(stdClass $parsed):?Address
    {
        try
        {
            $parsed=json_decode(json_encode($parsed), true);
            $properties=["denumire_Strada", "numar_Strada", "denumire_Localitate", "cod_Localitate", "denumire_Judet", "cod_Judet", "cod_JudetAuto", "tara", "detalii_Adresa", "cod_Postal"];
            $prefix="d";
            if(isset($parsed["scod_Localitate"]))
            {
                $prefix="s";
            }
            $result = new Address();
            for($i=0;$i<count($properties);$i++)
            {
                $property=$properties[$i];
                if(isset($parsed[$prefix.$property]))
                {
                    $result->$property = $parsed[$prefix . $property];
                }
            }
            return $result;
        }
        catch (\Throwable $th)
        {
            return null;
        }
    }
}