<?php declare(strict_types=1);

namespace EdituraEDU\ANAF\Tests\ParseTests;

use EdituraEDU\ANAF\Responses\ANAFVerifyResponse;
use PHPUnit\Framework\TestCase;
use Throwable;
class VerifyResponseTest extends TestCase
{
    public function testOKResponse(): void
    {
        $response = "{\"stare\":\"ok\",\"trace_id\":\"225f5b89-befc-4811-859f-cbd41bf0f0e6\"}";
        try {
            $verifyResponse = new ANAFVerifyResponse();
            $verifyResponse->rawResponse = $response;
            $verifyResponse->Parse();
            $this->assertTrue($verifyResponse->IsSuccess(), "Response is not successful");
            $this->assertTrue($verifyResponse->IsOK(), "Response is not OK");
        } catch (Throwable $ex) {
            $this->fail("Exception thrown: " . $ex->getMessage());
        }
    }

    public function testNotOkResponse(): void
    {
        $response = "{\"stare\":\"nok\",\"Messages\":[{\"message\":\"tipAssert=FailedAssert; codEroare=UBL-CR-661; localizareEroare=/Invoice; textEroare=[UBL-CR-661]-A UBL invoice should not include the PaymentMeansCode listID; expresieValidata=not(cac:PaymentMeans/cbc:PaymentMeansCode/@listID)\"},{\"message\":\"tipAssert=FailedAssert; codEroare=UBL-DT-17; localizareEroare=/Invoice; textEroare=[UBL-DT-17]-List name attribute should not be present; expresieValidata=not(//@listName)\"},{\"message\":\"tipAssert=FailedAssert; codEroare=UBL-DT-20; localizareEroare=/Invoice; textEroare=[UBL-DT-20]-List uri attribute should not be present; expresieValidata=not(//@listURI)\"}],\"trace_id\":\"0346459f-bd4b-48de-ac8e-cdb8d8a6c6fd\"}";
        try {
            $verifyResponse = new ANAFVerifyResponse();
            $verifyResponse->rawResponse = $response;
            $verifyResponse->Parse();
            $this->assertTrue($verifyResponse->IsSuccess(), "Response is not successful");
            $this->assertFalse($verifyResponse->IsOK(), "Response is OK, it should not be");
            $this->assertIsArray($verifyResponse->Messages, "Messages is not an array");
            $this->assertCount(3, $verifyResponse->Messages, "Messages count mismatch");
        } catch (Throwable $ex) {
            $this->fail("Exception thrown: " . $ex->getMessage());
        }
    }
}