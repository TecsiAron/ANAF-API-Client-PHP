<?php

namespace EdituraEDU\ANAF\Signature;

enum SignatureVerificationResult: string {
    /** Verification completed successfully. */
    case Success = 'SUCCESS';

    /** One or both invoice/signature files could not be read. */
    case FileReadError = 'FILE_READ_ERROR';

    /** The signature content is not valid XML or does not have a Signature root element. */
    case InvalidSignatureXml = 'INVALID_SIGNATURE_XML';

    /** The signature XML does not use the expected XML Digital Signature namespace. */
    case InvalidSignatureNamespace = 'INVALID_SIGNATURE_NAMESPACE';

    /** Required SignedInfo, SignatureValue, or X509Certificate data is missing. */
    case MissingSignatureData = 'MISSING_SIGNATURE_DATA';

    /** The digest in the signature does not match the SHA-256 digest of the invoice. */
    case DigestMismatch = 'DIGEST_MISMATCH';

    /** SignedInfo could not be imported into DOM or canonicalized. */
    case InvalidCanonicalData = 'INVALID_CANONICAL_DATA';

    /** The embedded certificate is empty or cannot be loaded as a public key. */
    case InvalidCertificateBlock = 'INVALID_CERTIFICATE_BLOCK';

    /** The SignatureValue is not valid base64 data. */
    case InvalidSignatureValue = 'INVALID_SIGNATURE_VALUE';

    /** The cryptographic signature does not validate against the canonicalized SignedInfo. */
    case SignatureMismatch = 'SIGNATURE_MISMATCH';

    /** The signature matches, but its certificate is not in the accepted list. */
    case SignatureMatchWithUnacceptedCertificate = 'SIGNATURE_MATCH_WITH_UNACCEPTED_CERTIFICATE';

    /** One or more required PHP extensions are not available. */
    case NotSupported = 'NOT_SUPPORTED';
}
