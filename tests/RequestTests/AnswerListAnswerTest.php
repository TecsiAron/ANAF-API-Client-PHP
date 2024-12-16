<?php

namespace EdituraEDU\ANAF\Tests\RequestTests;

use DateInterval;
use DateTime;
use EdituraEDU\ANAF\ANAFAPIClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DependsExternal;
use PHPUnit\Framework\Attributes\UsesFunction;
use Throwable;

class AnswerListAnswerTest extends RequestTestBase
{

    public function testEmptyResponse()
    {
        try {
            $client = $this->createClient();
            $response = $client->ListAnswers($this->cif, 1);
            $localParsed = json_decode($response->rawResponse);
        } catch (Throwable $ex) {
            $this->fail("Exception thrown: " . $ex->getMessage());
        }
        $this->assertTrue(json_last_error() == JSON_ERROR_NONE, "Invalid JSON response");
        $this->assertTrue($response->IsSuccess(), "Response is not successful");

        if (isset($localParsed->mesaje)) {
            $this->markTestSkipped("Response was not empty");
        } else {
            $this->assertTrue(isset($localParsed->titlu), "Invalid response: title not set");
            $this->assertEquals("lista mesaje", strtolower($localParsed->titlu), "Invalid response: title mismatch");
            $this->assertTrue(isset($localParsed->eroare), "Invalid response: error not set");
            $this->assertStringContainsString("nu exista mesaje", strtolower($localParsed->eroare), "Invalid response: error mismatch");
            $this->assertFalse(isset($localParsed->cui), "Invalid response: CUI should not be set");
            $this->assertFalse(isset($localParsed->serial), "Invalid response: serial should not be set");
            $this->assertTrue($response->IsSuccess(), "Response not successful");
            $this->assertCount(0, $response->mesaje, "Messages count mismatch");
        }
    }

    public function testValidResponseCheck()
    {
        if (getenv("ANAF_EMPTY_LIST_ONLY") == "true") {
            $this->markTestSkipped("ANAF_EMPTY_ONLY is set, skipping");
        }
        $validUBL = base64_decode(UploadUBLTest::VALID_UBL);
        $listAnswer = null;
        try {
            $client = $this->createClient();
            $result = $client->UploadEFactura($validUBL, $this->cif);
            $this->assertTrue($result->IsSuccess(), "Test upload failed");
            sleep(1);
            $listAnswer = $client->ListAnswers($this->cif, 1);
        } catch (Throwable $ex) {
            $this->fail("Exception thrown: " . $ex->getMessage());
        }
        $this->assertNotNull($listAnswer, "No answer found");
        $this->assertTrue($listAnswer->IsSuccess(), "Failed to list answers");
        $this->assertIsArray($listAnswer->mesaje, "Messages is not an array");
        $this->assertGreaterThan(0, count($listAnswer->mesaje), "No messages found");
    }

    public function testAnswersListWithPages()
    {
        $client = $this->createClient();
        $answers = $client->ListAnswers($this->cif, 10);
        $this->assertTrue($answers->IsSuccess());
        $this->assertIsArray($answers->mesaje);
        $startTime = new DateTime();
        $startTime->sub(new DateInterval("P10D"));
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        $startTime->setTime(0, 0, 0);
        $endTime = new DateTime();
        $endTime->sub(new DateInterval("PT60S"));
        $pagedAnswers = $client->ListAnswersWithPagination($startTime->getTimestamp(), $endTime->getTimestamp(), $this->cif);
        $errorMessage = $pagedAnswers->IsSuccess() ? "" : $pagedAnswers->LastError->getMessage();
        $this->assertTrue($pagedAnswers->IsSuccess(), $errorMessage);
        $this->assertIsArray($pagedAnswers->mesaje);
        for ($i = 0; $i < count($pagedAnswers->mesaje); $i++) {
            $paged = $pagedAnswers->mesaje[$i];
            $found = false;
            for ($j = 0; $j < count($answers->mesaje); $j++) {
                $original = $answers->mesaje[$j];
                if ($original->id == $paged->id) {
                    $found = true;
                    $this->assertEquals($original->detalii, $paged->detalii);
                    break 2;
                }
            }
            $this->assertTrue($found, "Message not found in original list");
        }
    }


}