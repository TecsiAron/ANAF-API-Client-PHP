<?php declare(strict_types=1);

namespace EdituraEDU\ANAF\Tests\ParseTests;

use EdituraEDU\ANAF\Responses\ANAFException;
use EdituraEDU\ANAF\Responses\UBLUploadResponse;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * @covers \EdituraEDU\ANAF\Responses\UBLUploadResponse
 */
class UploadResponseTest extends TestCase
{
    public function testSuccessResponse(): void
    {
        $response = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\r<header xmlns=\"mfp:anaf:dgti:spv:respUploadFisier:v1\" dateResponse=\"202404041741\" ExecutionStatus=\"0\" index_incarcare=\"1234567890\"/>";
        try {
            $uploadResponse = new UBLUploadResponse();
            $uploadResponse->rawResponse = $response;
            $uploadResponse->Parse();
            $this->assertTrue($uploadResponse->IsSuccess());
            $this->assertEquals(1234567890, $uploadResponse->IndexIncarcare, "Wrong index_incarcare");
            $this->assertEquals(1712252460, $uploadResponse->ResponseTimestamp, "Wrong timestamp");
        } catch (Throwable $ex) {
            $this->fail("Exception thrown: " . $ex->getMessage());
        }
    }

    public function testBadResponse(): void
    {
        $response = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?>\r<header xmlns=\"mfp:anaf:dgti:spv:respUploadFisier:v1\" dateResponse=\"202404041752\" ExecutionStatus=\"1\">\r    <Errors errorMessage=\"Fisierul transmis nu este valid. org.xml.sax.SAXParseException; lineNumber: 1; columnNumber: 1; Content is not allowed in prolog.\"/>\r</header>";
        try {
            $uploadResponse = new UBLUploadResponse();
            $uploadResponse->rawResponse = $response;
            $uploadResponse->Parse();
            $this->assertTrue($uploadResponse->HasError(), "Response does not have error");
            $this->assertEquals(ANAFException::REMOTE_EXCEPTION, $uploadResponse->LastError->getCode(), "Error code mismatch");
        } catch (Throwable $ex) {
            $this->fail("Exception thrown: " . $ex->getMessage());
        }
    }

    public function testBadRequest()
    {
        $response = "{\r  \"timestamp\": \"05-08-2021 12:04:01\",\r  \"status\": 400,\r  \"error\": \"Bad Request\",\r  \"message\": \"Trebuie sa aveti atasat in request un fisier de tip xml\"\r}";
        try {
            $uploadResponse = new UBLUploadResponse();
            $uploadResponse->rawResponse = $response;
            $uploadResponse->Parse();
            $this->assertTrue($uploadResponse->HasError(), "Response does not have error");
            $this->assertEquals(ANAFException::REMOTE_EXCEPTION, $uploadResponse->LastError->getCode(), "Error code mismatch");
        } catch (Throwable $ex) {
            $this->fail("Exception thrown: " . $ex->getMessage());
        }
    }
}