<?php declare(strict_types=1);

namespace EdituraEDU\ANAF\Tests\ParseTests;

use EdituraEDU\ANAF\Responses\ANAFAnswerListResponse;
use EdituraEDU\ANAF\Responses\ANAFException;
use PHPUnit\Framework\TestCase;
use Throwable;

class AnswerListTest extends TestCase
{
    public function testValidAnswerList(): void
    {
        $response = "{\"mesaje\":[{\"data_creare\":\"202404041752\",\"cif\":\"12345678\",\"id_solicitare\":\"1234567890\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=1234567890\",\"tip\":\"ERORI FACTURA\",\"id\":\"0123456789\"},{\"data_creare\":\"202404041745\",\"cif\":\"12345678\",\"id_solicitare\":\"7894561230\",\"detalii\":\"Factura cu id_incarcare=7894561230 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"9638527418\"},{\"data_creare\":\"202404041744\",\"cif\":\"12345678\",\"id_solicitare\":\"0741852963\",\"detalii\":\"Factura cu id_incarcare=0741852963 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"1346798520\"},{\"data_creare\":\"202404041136\",\"cif\":\"12345678\",\"id_solicitare\":\"9764312589\",\"detalii\":\"Factura cu id_incarcare=9764312589 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"1122334455\"},{\"data_creare\":\"202404041141\",\"cif\":\"12345678\",\"id_solicitare\":\"9988776655\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=9988776655\",\"tip\":\"ERORI FACTURA\",\"id\":\"3322116655\"},{\"data_creare\":\"202404041141\",\"cif\":\"12345678\",\"id_solicitare\":\"9966338855\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=9966338855\",\"tip\":\"ERORI FACTURA\",\"id\":\"0000000000\"},{\"data_creare\":\"202404041137\",\"cif\":\"12345678\",\"id_solicitare\":\"0000000001\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=0000000001\",\"tip\":\"ERORI FACTURA\",\"id\":\"0000000002\"},{\"data_creare\":\"202404041137\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000002\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=1000000002\",\"tip\":\"ERORI FACTURA\",\"id\":\"1000000000\"},{\"data_creare\":\"202404041136\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000001\",\"detalii\":\"Factura cu id_incarcare=1000000001 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"1000000004\"},{\"data_creare\":\"202404041752\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000005\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=1000000005\",\"tip\":\"ERORI FACTURA\",\"id\":\"1000000006\"},{\"data_creare\":\"202404041753\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000007\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=1000000007\",\"tip\":\"ERORI FACTURA\",\"id\":\"1000000008\"},{\"data_creare\":\"202404041618\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000009\",\"detalii\":\"Factura cu id_incarcare=1000000009 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"1000000010\"},{\"data_creare\":\"202404041621\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000011\",\"detalii\":\"Factura cu id_incarcare=1000000011 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"1000000012\"},{\"data_creare\":\"202404041621\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000013\",\"detalii\":\"Factura cu id_incarcare=1000000013 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"1000000014\"}],\"serial\":\"ffffffffffffffffffffffffffffff\",\"cui\":\"0123456789012,12345678\",\"titlu\":\"Lista Mesaje disponibile din ultimele 60 zile\"}";
        $expected = "[{\"data_creare\":\"202404041752\",\"cif\":\"12345678\",\"id_solicitare\":\"1234567890\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=1234567890\",\"tip\":\"ERORI FACTURA\",\"id\":\"0123456789\"},{\"data_creare\":\"202404041745\",\"cif\":\"12345678\",\"id_solicitare\":\"7894561230\",\"detalii\":\"Factura cu id_incarcare=7894561230 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"9638527418\"},{\"data_creare\":\"202404041744\",\"cif\":\"12345678\",\"id_solicitare\":\"0741852963\",\"detalii\":\"Factura cu id_incarcare=0741852963 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"1346798520\"},{\"data_creare\":\"202404041136\",\"cif\":\"12345678\",\"id_solicitare\":\"9764312589\",\"detalii\":\"Factura cu id_incarcare=9764312589 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"1122334455\"},{\"data_creare\":\"202404041141\",\"cif\":\"12345678\",\"id_solicitare\":\"9988776655\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=9988776655\",\"tip\":\"ERORI FACTURA\",\"id\":\"3322116655\"},{\"data_creare\":\"202404041141\",\"cif\":\"12345678\",\"id_solicitare\":\"9966338855\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=9966338855\",\"tip\":\"ERORI FACTURA\",\"id\":\"0000000000\"},{\"data_creare\":\"202404041137\",\"cif\":\"12345678\",\"id_solicitare\":\"0000000001\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=0000000001\",\"tip\":\"ERORI FACTURA\",\"id\":\"0000000002\"},{\"data_creare\":\"202404041137\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000002\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=1000000002\",\"tip\":\"ERORI FACTURA\",\"id\":\"1000000000\"},{\"data_creare\":\"202404041136\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000001\",\"detalii\":\"Factura cu id_incarcare=1000000001 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"1000000004\"},{\"data_creare\":\"202404041752\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000005\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=1000000005\",\"tip\":\"ERORI FACTURA\",\"id\":\"1000000006\"},{\"data_creare\":\"202404041753\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000007\",\"detalii\":\"Erori de validare identificate la factura primita cu id_incarcare=1000000007\",\"tip\":\"ERORI FACTURA\",\"id\":\"1000000008\"},{\"data_creare\":\"202404041618\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000009\",\"detalii\":\"Factura cu id_incarcare=1000000009 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"1000000010\"},{\"data_creare\":\"202404041621\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000011\",\"detalii\":\"Factura cu id_incarcare=1000000011 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"1000000012\"},{\"data_creare\":\"202404041621\",\"cif\":\"12345678\",\"id_solicitare\":\"1000000013\",\"detalii\":\"Factura cu id_incarcare=1000000013 emisa de cif_emitent=12345678 pentru cif_beneficiar=10000003\",\"tip\":\"FACTURA TRIMISA\",\"id\":\"1000000014\"}]";
        try {
            $answerList = new ANAFAnswerListResponse();
            $answerList->rawResponse = $response;
            $answerList->Parse();
            $this->assertTrue($answerList->IsSuccess());
            $this->assertEquals($expected, json_encode($answerList->mesaje), "Unexpected content");
        } catch (Throwable $ex) {
            $this->fail("Exception thrown: " . $ex->getMessage());
        }
    }

    public function testNoAnswers(): void
    {
        $response = "{\"eroare\":\"Nu exista mesaje in ultimele 5 zile\",\"titlu\":\"Lista Mesaje\"}";
        try {
            $answerList = new ANAFAnswerListResponse();
            $answerList->rawResponse = $response;
            $answerList->Parse();
            $this->assertTrue($answerList->IsSuccess(), "Response is not successful");
            $this->assertCount(0, $answerList->mesaje, "Wrong answer count");
        } catch (Throwable $ex) {
            $this->fail("Exception thrown: " . $ex->getMessage());
        }
    }
    public function testRemoteError(): void
    {
        $response = "{\"eroare\":\"Numarul de zile trebuie sa fie intre 1 si 60\",\"titlu\":\"Lista Mesaje\"}";
        try {
            $answerList = new ANAFAnswerListResponse();
            $answerList->rawResponse = $response;
            $answerList->Parse();
            $this->assertTrue($answerList->HasError(), "Response has no error");
            $this->assertEquals(ANAFException::REMOTE_EXCEPTION, $answerList->LastError->getCode(), "Error code mismatch");
        } catch (Throwable $ex) {
            $this->fail("Exception thrown: " . $ex->getMessage());
        }
    }
}