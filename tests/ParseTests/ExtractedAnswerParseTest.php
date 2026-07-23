<?php

namespace EdituraEDU\ANAF\Tests\ParseTests;

use EdituraEDU\ANAF\Tests\ANAFTestDatastore;
use EdituraEDU\ANAF\Responses\ExtractedAnswer;
use PHPUnit\Framework\TestCase;

class ExtractedAnswerParseTest extends TestCase {

    public function testExtractAnswer() {
        $answer = ExtractedAnswer::Create(base64_decode(ANAFTestDatastore::VALID_ZIP_BASE64), false);
        $this->assertNotNull($answer);
        $this->assertTrue($answer->IsSuccess());
        $this->assertNotEmpty($answer->content);
        $this->assertNotEmpty($answer->signature);
        $this->assertFalse($answer->RanSignatureVerification);
        $this->assertTrue($answer->IsWellFormedError);
        $this->assertNotNull($answer->Error);
        $this->assertTrue($answer->Error->IsDuplicateUploadError());
    }
}
