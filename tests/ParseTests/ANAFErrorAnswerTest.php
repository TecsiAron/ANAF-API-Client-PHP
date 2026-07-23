<?php

namespace EdituraEDU\ANAF\Tests\ParseTests;

use EdituraEDU\ANAF\Tests\ANAFTestDatastore;
use EdituraEDU\ANAF\Responses\ANAFErrorAnswer;
use EdituraEDU\ANAF\Responses\ANAFException;
use PHPUnit\Framework\TestCase;

class ANAFErrorAnswerTest extends TestCase {

    public function testParseError(): void {
        $answer = ANAFErrorAnswer::Create("test-1", ANAFTestDatastore::ERROR_XML);
        $this->assertTrue($answer->IsSuccess());
        $this->assertNull($answer->LastError);
        $this->assertEquals("98765432", $answer->cif_emitent);
        $this->assertEquals("0123456789", $answer->index_incarcare);
        $this->assertTrue($answer->IsDuplicateUploadError());
    }

    public function testValidInvoice(): void {
        $answer = ANAFErrorAnswer::Create("test-2", ANAFTestDatastore::VALID_INVOICE);
        $this->assertFalse($answer->IsSuccess());
        $this->assertNotNull($answer->LastError);
        $this->assertEquals(ANAFException::EXPECTED_ERROR_GOT_VALID_ANSWER, $answer->LastError->getCode());
    }

    public function testInvalidXml(): void {
        $answer = ANAFErrorAnswer::Create("test-3", "Invalid XML");
        $this->assertFalse($answer->IsSuccess());
        $this->assertNotNull($answer->LastError);
        $this->assertEquals(ANAFException::ERROR_ANSWER_PARSE_FAILED, $answer->LastError->getCode());
    }

    public function testExpectedErrorFormat(): void {
        $this->assertTrue(ANAFErrorAnswer::IsExpectedErrorFormat(ANAFTestDatastore::ERROR_XML));
        $this->assertFalse(ANAFErrorAnswer::IsExpectedErrorFormat(ANAFTestDatastore::VALID_INVOICE));
        $this->assertFalse(ANAFErrorAnswer::IsExpectedErrorFormat("Invalid XML"));
    }
}
