<?php

namespace EdituraEDU\ANAF\Signature;

use SimpleXMLElement;

final class EFacturaSignatureVerifier {
    private const DSIG_NAMESPACE = 'http://www.w3.org/2000/09/xmldsig#';

    public static function IsSupported(): bool {
        return function_exists('simplexml_load_string')
                && function_exists('libxml_use_internal_errors')
                && function_exists('dom_import_simplexml')
                && function_exists('openssl_pkey_get_public')
                && function_exists('openssl_verify');
    }

    public static function IsCertificateAccepted(string $normalizedCertificate): bool {
        $normalizedCertificate = self::normalizeCertificate($normalizedCertificate);

        if ($normalizedCertificate === '') {
            return false;
        }

        $acceptedCertificatesPath = dirname(__DIR__, 2).'/AcceptedCerts';
        $acceptedCertificateFiles = glob($acceptedCertificatesPath.'/*.pem') ?: [];

        foreach ($acceptedCertificateFiles as $acceptedCertificateFile) {
            $acceptedCertificate = file_get_contents($acceptedCertificateFile);

            if ($acceptedCertificate === false) {
                continue;
            }

            if (hash_equals($normalizedCertificate, self::normalizeCertificate($acceptedCertificate))) {
                return true;
            }
        }
        return false;
    }

    public static function VerifyInvoicesFile(string $invoicePath, string $signaturePath): SignatureVerificationResult {
        if ( ! self::IsSupported()) {
            return SignatureVerificationResult::NotSupported;
        }

        $invoiceBytes = file_get_contents($invoicePath);
        $signatureXml = file_get_contents($signaturePath);

        if ($invoiceBytes === false || $signatureXml === false) {
            return SignatureVerificationResult::FileReadError;
        }
        return self::VerifyContent($invoiceBytes, $signatureXml);
    }

    public static function VerifyContent(string $invoiceBytes, string $signatureXml): SignatureVerificationResult {
        if ( ! self::IsSupported()) {
            return SignatureVerificationResult::NotSupported;
        }

        libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($signatureXml, SimpleXMLElement::class, LIBXML_NONET);

            if ($xml === false || $xml->getName() !== 'Signature') {
                return SignatureVerificationResult::InvalidSignatureXml;
            }

            $namespaces = $xml->getDocNamespaces();

            if (($namespaces[''] ?? null) !== self::DSIG_NAMESPACE) {
                return SignatureVerificationResult::InvalidSignatureNamespace;
            }

            /*
             * The signature uses a default XML namespace, so access its
             * children through that namespace explicitly.
             */
            $signature = $xml->children(self::DSIG_NAMESPACE);
            $signedInfo = $signature->SignedInfo;

            if ( ! isset($signedInfo->Reference->DigestValue)
                    || ! isset($signature->SignatureValue)
                    || ! isset($signature->KeyInfo->X509Data->X509Certificate)) {
                return SignatureVerificationResult::MissingSignatureData;
            }

            /*
             * Verify the invoice's SHA-256 digest.
             *
             * $invoiceBytes must be the exact original bytes extracted
             * from the ZIP.
             */
            $expectedDigest = base64_decode(
                    self::normalizeBase64(
                            (string) $signedInfo->Reference->DigestValue
                    ),
                    true
            );

            if (
                    $expectedDigest === false
                    || ! hash_equals(
                            $expectedDigest,
                            hash('sha256', $invoiceBytes, true)
                    )
            ) {
                return SignatureVerificationResult::DigestMismatch;
            }

            /*
             * Properly canonicalize SignedInfo using C14N 1.0.
             */
            $signedInfoDom = dom_import_simplexml($signedInfo);

            if ($signedInfoDom === false) {
                return SignatureVerificationResult::InvalidCanonicalData;
            }

            $canonicalSignedInfo = $signedInfoDom->C14N(false, false);

            if ($canonicalSignedInfo === false) {
                return SignatureVerificationResult::InvalidCanonicalData;
            }

            /*
             * Build a PEM certificate from the certificate embedded
             * inside the XML signature.
             */
            $certificateBase64 = self::normalizeBase64(
                    (string) $signature
                            ->KeyInfo
                            ->X509Data
                            ->X509Certificate
            );

            if ($certificateBase64 === '') {
                return SignatureVerificationResult::InvalidCertificateBlock;
            }

            $certificatePem = "-----BEGIN CERTIFICATE-----\n".chunk_split($certificateBase64, 64, "\n")."-----END CERTIFICATE-----\n";

            $publicKey = openssl_pkey_get_public($certificatePem);

            if ($publicKey === false) {
                return SignatureVerificationResult::InvalidCertificateBlock;
            }

            $signatureBytes = base64_decode(
                    self::normalizeBase64(
                            (string) $signature->SignatureValue
                    ),
                    true
            );

            if ($signatureBytes === false) {
                return SignatureVerificationResult::InvalidSignatureValue;
            }

            /*
             * The supplied MF sample declares RSA-SHA1.
             */

            $matches = openssl_verify($canonicalSignedInfo, $signatureBytes, $publicKey, OPENSSL_ALGO_SHA1) === 1;

            if ($matches) {
                if ( ! self::IsCertificateAccepted($certificateBase64)) {
                    return SignatureVerificationResult::SignatureMatchWithUnacceptedCertificate;
                }
                return SignatureVerificationResult::Success;
            }
            return SignatureVerificationResult::SignatureMismatch;
        } finally {
            libxml_clear_errors();
        }
    }

    private static function normalizeBase64(string $value): string {
        return preg_replace('/\s+/', '', $value) ?? '';
    }

    private static function normalizeCertificate(string $value): string {
        return self::normalizeBase64(
                str_replace(
                        [
                                '-----BEGIN CERTIFICATE-----',
                                '-----END CERTIFICATE-----',
                        ],
                        '',
                        $value
                )
        );
    }
}
