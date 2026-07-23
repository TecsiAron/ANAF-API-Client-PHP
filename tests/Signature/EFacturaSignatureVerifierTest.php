<?php

declare(strict_types=1);

namespace EdituraEDU\ANAF\Tests\Signature;

use EdituraEDU\ANAF\Tests\ANAFTestDatastore;
use EdituraEDU\ANAF\Responses\ExtractedAnswer;
use EdituraEDU\ANAF\Signature\EFacturaSignatureVerifier;
use EdituraEDU\ANAF\Signature\SignatureVerificationResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EFacturaSignatureVerifierTest extends TestCase {





    public static function verificationCases(): array {
        return [
                'valid answer' => [
                        ANAFTestDatastore::VALID_ANSWER_BASE64,
                        SignatureVerificationResult::Success,
                ],
                'digest mismatch' => [
                        ANAFTestDatastore::DIGEST_MISSMATCH_ANSWER_BASE64,
                        SignatureVerificationResult::DigestMismatch,
                ],
                'signature mismatch' => [
                        ANAFTestDatastore::SIGNATURE_MISSMATCH_ANSWER_BASE64,
                        SignatureVerificationResult::SignatureMismatch,
                ],
        ];
    }

    #[DataProvider('verificationCases')]
    public function testEmbeddedAnswerVerification(
            string $answerBase64,
            SignatureVerificationResult $expectedResult
    ): void {
        $answer = ExtractedAnswer::Create(base64_decode($answerBase64, true), false);

        $this->assertTrue($answer->IsSuccess());
        $this->assertNotEmpty($answer->content);
        $this->assertNotEmpty($answer->signature);

        $result = EFacturaSignatureVerifier::VerifyContent(
                $answer->content,
                $answer->signature
        );

        $this->assertSame($expectedResult, $result);
    }

    public function testCertificateAcceptance(): void {
        $acceptedCertificate = base64_decode(ANAFTestDatastore::ACCEPTED_CERTIFICATE_BASE64, true);
        $invalidCertificate = base64_decode(ANAFTestDatastore::INVALID_CERTIFICATE_BASE64, true);

        $this->assertIsString($acceptedCertificate);
        $this->assertIsString($invalidCertificate);
        $this->assertTrue(EFacturaSignatureVerifier::IsCertificateAccepted($acceptedCertificate));
        $this->assertFalse(EFacturaSignatureVerifier::IsCertificateAccepted($invalidCertificate));
    }
}
