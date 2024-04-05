<?php declare(strict_types=1);

namespace EdituraEDU\ANAF\Tests\ParseTests;

use EdituraEDU\ANAF\Responses\ANAFException;
use EdituraEDU\ANAF\Responses\EntityResponse;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * @covers \EdituraEDU\ANAF\Responses\EntityResponse
 */
class EntityResponseTest extends TestCase
{
    public function testParseNoError(): void
    {
        $response = "{\"cod\":200,\"message\":\"SUCCESS\",\"found\":[{\"date_generale\":{\"cui\":12345678,\"data\":\"2024-04-04\",\"denumire\":\"test S.R.L.\",\"adresa\":\"test adresa\",\"nrRegCom\":\"J26/1234/2007\",\"telefon\":\"0740000000\",\"fax\":\"\",\"codPostal\":\"123456\",\"act\":\"\",\"stare_inregistrare\":\"INREGISTRAT din data 01.01.2000\",\"data_inregistrare\":\"2000-01-01\",\"cod_CAEN\":\"5814\",\"iban\":\"\",\"statusRO_e_Factura\":false,\"organFiscalCompetent\":\"Administraţia Judeţeană a Finanţelor Publice Mureş\",\"forma_de_proprietate\":\"PROPR.PRIVATA-CAPITAL PRIVAT AUTOHTON\",\"forma_organizare\":\"PERSOANA JURIDICA\",\"forma_juridica\":\"SOCIETATE COMERCIALĂ CU RĂSPUNDERE LIMITATĂ\"},\"inregistrare_scop_Tva\":{\"scpTVA\":true,\"perioade_TVA\":[{\"data_inceput_ScpTVA\":\"2012-01-01\",\"data_sfarsit_ScpTVA\":\"\",\"data_anul_imp_ScpTVA\":\"\",\"mesaj_ScpTVA\":\"\"}]},\"inregistrare_RTVAI\":{\"dataInceputTvaInc\":\"2013-01-01\",\"dataSfarsitTvaInc\":\"2016-12-01\",\"dataActualizareTvaInc\":\"2016-11-23\",\"dataPublicareTvaInc\":\"2016-11-24\",\"tipActTvaInc\":\"Radiere\",\"statusTvaIncasare\":false},\"stare_inactiv\":{\"dataInactivare\":\"\",\"dataReactivare\":\"\",\"dataPublicare\":\"\",\"dataRadiere\":\"\",\"statusInactivi\":false},\"inregistrare_SplitTVA\":{\"dataInceputSplitTVA\":\"\",\"dataAnulareSplitTVA\":\"\",\"statusSplitTVA\":false},\"adresa_sediu_social\":{\"sdenumire_Strada\":\"Str. Avram Iancu\",\"snumar_Strada\":\"46\",\"sdenumire_Localitate\":\"Mun. Târgu Mureş\",\"scod_Localitate\":\"123\",\"sdenumire_Judet\":\"MUREŞ\",\"scod_Judet\":\"10\",\"scod_JudetAuto\":\"XX\",\"stara\":\"\",\"sdetalii_Adresa\":\"\",\"scod_Postal\":\"540090\"},\"adresa_domiciliu_fiscal\":{\"ddenumire_Strada\":\"Str. Avram Iancu\",\"dnumar_Strada\":\"46\",\"ddenumire_Localitate\":\"Mun. Târgu Mureş\",\"dcod_Localitate\":\"499\",\"ddenumire_Judet\":\"MUREŞ\",\"dcod_Judet\":\"26\",\"dcod_JudetAuto\":\"MS\",\"dtara\":\"\",\"ddetalii_Adresa\":\"\",\"dcod_Postal\":\"123456\"}}],\"notFound\":[]}";
        $expected = "{\"date_generale\":{\"cui\":\"12345678\",\"data\":\"2024-04-04\",\"denumire\":\"test S.R.L.\",\"adresa\":\"test adresa\",\"nrRegCom\":\"J26\\/1234\\/2007\",\"telefon\":\"0740000000\",\"fax\":\"\",\"codPostal\":\"123456\",\"act\":\"\",\"stare_inregistrare\":\"INREGISTRAT din data 01.01.2000\",\"cod_CAEN\":\"5814\",\"iban\":\"\",\"statusRO_e_Factura\":false,\"organFiscalCompetent\":\"Administra\\u0163ia Jude\\u0163ean\\u0103 a Finan\\u0163elor Publice Mure\\u015f\",\"forma_de_proprietate\":\"PROPR.PRIVATA-CAPITAL PRIVAT AUTOHTON\",\"forma_organizare\":\"PERSOANA JURIDICA\",\"forma_juridica\":\"SOCIETATE COMERCIAL\\u0102 CU R\\u0102SPUNDERE LIMITAT\\u0102\"},\"inregistrare_scop_Tva\":{\"scpTVA\":true,\"perioade_TVA\":[{\"data_inceput_ScpTVA\":\"2012-01-01\",\"data_sfarsit_ScpTVA\":\"\",\"data_anul_imp_ScpTVA\":\"\",\"mesaj_ScpTVA\":\"\"}]},\"inregistrare_RTVAI\":{\"dataInceputTvaInc\":\"2013-01-01\",\"dataSfarsitTvaInc\":\"2016-12-01\",\"dataActualizareTvaInc\":\"2016-11-23\",\"dataPublicareTvaInc\":\"2016-11-24\",\"tipActTvaInc\":\"Radiere\",\"statusTvaIncasare\":false},\"stare_inactiv\":{\"dataInactivare\":\"\",\"dataReactivare\":\"\",\"dataPublicare\":\"\",\"dataRadiere\":\"\",\"statusInactivi\":false},\"inregistrare_SplitTVA\":{\"dataInceputSplitTVA\":\"\",\"dataAnulareSplitTVA\":\"\",\"statusSplitTVA\":false},\"adresa_sediu_social\":{\"denumire_Strada\":\"Str. Avram Iancu\",\"numar_Strada\":\"46\",\"denumire_Localitate\":\"Mun. T\\u00e2rgu Mure\\u015f\",\"cod_Localitate\":\"123\",\"denumire_Judet\":\"MURE\\u015e\",\"cod_Judet\":\"10\",\"cod_JudetAuto\":\"XX\",\"tara\":\"\",\"detalii_Adresa\":\"\",\"cod_Postal\":\"540090\"},\"adresa_domiciliu_fiscal\":{\"denumire_Strada\":\"Str. Avram Iancu\",\"numar_Strada\":\"46\",\"denumire_Localitate\":\"Mun. T\\u00e2rgu Mure\\u015f\",\"cod_Localitate\":\"499\",\"denumire_Judet\":\"MURE\\u015e\",\"cod_Judet\":\"26\",\"cod_JudetAuto\":\"MS\",\"tara\":\"\",\"detalii_Adresa\":\"\",\"cod_Postal\":\"123456\"}}";
        try {
            $entityResponse = new EntityResponse();
            $entityResponse->rawResponse = $response;
            $entityResponse->Parse();
            $this->assertTrue($entityResponse->Entity != null, "Entity is null");
            $this->assertFalse($entityResponse->HasError(), "Response has error");
            $this->assertEquals($expected, json_encode($entityResponse->Entity), "Entity unexpected content");
        } catch (Throwable $ex) {
            $this->fail("Exception thrown: " . $ex->getMessage());
        }
    }

    public function testNotFoundValidCIF(): void
    {
        $response = "{\"cod\":200,\"message\":\"SUCCESS\",\"found\":[],\"notFound\":[12345678]}";
        try {
            $entityResponse = new EntityResponse();
            $entityResponse->rawResponse = $response;
            $entityResponse->Parse();
            $this->assertTrue($entityResponse->Entity == null, "Entity is not null");
            $this->assertTrue($entityResponse->HasError(), "Response has no error");
            $this->assertEquals(ANAFException::RESULT_NOT_FOUND, $entityResponse->LastError->getCode(), "Error code mismatch");
        } catch (Throwable $ex) {
            $this->fail("Exception thrown: " . $ex->getMessage());
        }
    }
}