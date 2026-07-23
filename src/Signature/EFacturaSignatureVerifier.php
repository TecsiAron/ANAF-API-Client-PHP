<?php

namespace EdituraEDU\ANAF\Signature;

use SimpleXMLElement;

final class EFacturaSignatureVerifier {
    private const DSIG_NAMESPACE = 'http://www.w3.org/2000/09/xmldsig#';

    /** Verification completed successfully. */
    public const SUCCESS = 'SUCCESS';

    /** One or both invoice/signature files could not be read. */
    public const FILE_READ_ERROR = 'FILE_READ_ERROR';

    /** The signature content is not valid XML or does not have a Signature root element. */
    public const INVALID_SIGNATURE_XML = 'INVALID_SIGNATURE_XML';

    /** The signature XML does not use the expected XML Digital Signature namespace. */
    public const INVALID_SIGNATURE_NAMESPACE = 'INVALID_SIGNATURE_NAMESPACE';

    /** Required SignedInfo, SignatureValue, or X509Certificate data is missing. */
    public const MISSING_SIGNATURE_DATA = 'MISSING_SIGNATURE_DATA';

    /** The digest in the signature does not match the SHA-256 digest of the invoice. */
    public const DIGEST_MISSMATCH = 'DIGEST_MISMATCH';

    /** SignedInfo could not be imported into DOM or canonicalized. */
    public const INVALID_CANONICAL_DATA = 'INVALID_CANONICAL_DATA';

    /** The embedded certificate is empty or cannot be loaded as a public key. */
    public const INVALID_CERTIFICATE_BLOCK = 'INVALID_CERTIFICATE_BLOCK';

    /** The SignatureValue is not valid base64 data. */
    public const INVALID_SIGNATURE_VALUE = 'INVALID_SIGNATURE_VALUE';

    /** The cryptographic signature does not validate against the canonicalized SignedInfo. */
    public const SIGNATURE_MISSMATCH = 'SIGNATURE_MISMATCH';

    /** The cryptographic signature validates, but the certificate is not in the accepted list. IMPORTANT: Should be treated as a warning/flag for manual verification since ANAF can change certificates and new certificates will be issued with time (Expecting new certs for 2028+). */
    public const SIGNATURE_MATCH_WITH_UNACCEPTED_CERTIFICATE = 'SIGNATURE_MATCH_WITH_UNACCEPTED_CERTIFICATE';

    /**
     * One or more required PHP extensions are not available (openssl, libxml, SimpleXML).
     */
    public const NOT_SUPPORTED = 'NOT_SUPPORTED';

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

    public static function VerifyInvoicesFile(string $invoicePath, string $signaturePath): string {
        if ( ! self::IsSupported()) {
            return self::NOT_SUPPORTED;
        }

        $invoiceBytes = file_get_contents($invoicePath);
        $signatureXml = file_get_contents($signaturePath);

        if ($invoiceBytes === false || $signatureXml === false) {
            return self::FILE_READ_ERROR;
        }

        return self::VerifyContent($invoiceBytes, $signatureXml);
    }

    public static function VerifyContent(string $invoiceBytes, string $signatureXml): string {
        if ( ! self::IsSupported()) {
            return self::NOT_SUPPORTED;
        }

        libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($signatureXml, SimpleXMLElement::class, LIBXML_NONET);

            if ($xml === false || $xml->getName() !== 'Signature') {
                return self::INVALID_SIGNATURE_XML;
            }

            $namespaces = $xml->getDocNamespaces();

            if (($namespaces[''] ?? null) !== self::DSIG_NAMESPACE) {
                return self::INVALID_SIGNATURE_NAMESPACE;
            }

            /*
             * The signature uses a default XML namespace, so access its
             * children through that namespace explicitly.
             */
            $signature  = $xml->children(self::DSIG_NAMESPACE);
            $signedInfo = $signature->SignedInfo;

            if ( ! isset($signedInfo->Reference->DigestValue)
                    || ! isset($signature->SignatureValue)
                    || ! isset($signature->KeyInfo->X509Data->X509Certificate)) {
                return self::MISSING_SIGNATURE_DATA;
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
                return self::DIGEST_MISSMATCH;
            }

            /*
             * Properly canonicalize SignedInfo using C14N 1.0.
             */
            $signedInfoDom = dom_import_simplexml($signedInfo);

            if ($signedInfoDom === false) {
                return self::INVALID_CANONICAL_DATA;
            }

            $canonicalSignedInfo = $signedInfoDom->C14N(false, false);

            if ($canonicalSignedInfo === false) {
                return self::INVALID_CANONICAL_DATA;
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
                return self::INVALID_CERTIFICATE_BLOCK;
            }

            $certificatePem =
                    "-----BEGIN CERTIFICATE-----\n"
                    .chunk_split($certificateBase64, 64, "\n")
                    ."-----END CERTIFICATE-----\n";

            $publicKey = openssl_pkey_get_public($certificatePem);

            if ($publicKey === false) {
                return self::INVALID_CERTIFICATE_BLOCK;
            }

            $signatureBytes = base64_decode(
                    self::normalizeBase64(
                            (string) $signature->SignatureValue
                    ),
                    true
            );

            if ($signatureBytes === false) {
                return self::INVALID_SIGNATURE_VALUE;
            }

            /*
             * The supplied MF sample declares RSA-SHA1.
             */

            $matches = openssl_verify($canonicalSignedInfo, $signatureBytes, $publicKey, OPENSSL_ALGO_SHA1) === 1;

            if ($matches) {
                if ( ! self::IsCertificateAccepted($certificateBase64)) {
                    return self::SIGNATURE_MATCH_WITH_UNACCEPTED_CERTIFICATE;
                }

                return self::SUCCESS;
            }

            return self::SIGNATURE_MISSMATCH;
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
