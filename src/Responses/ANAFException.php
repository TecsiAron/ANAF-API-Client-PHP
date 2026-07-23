<?php

namespace EdituraEDU\ANAF\Responses;

use Exception;
use Throwable;

class ANAFException extends Exception {
    const UNKNOWN_ERROR = 1;
    const EMPTY_RAW_RESPONSE = 2;
    const JSON_UNKNOWN_ERROR = 3;
    const JSON_PARSE_ERROR = 4;
    const UNEXPECTED_RESPONSE_STRUCTURE = 5;
    const RESULT_NOT_FOUND = 6;
    const TOO_MANY_RESULTS = 7;
    const INCOMPLETE_RESPONSE = 8;
    const INVALID_INPUT = 9;
    const HTTP_ERROR = 10;
    const REMOTE_EXCEPTION = 11;
    const COMPOUND_ERROR = 12;
    const MESSAGE_LIST_TOO_LONG = 13;
    const ERROR_ANSWER_PARSE_FAILED = 14;
    const EXPECTED_ERROR_GOT_VALID_ANSWER = 15;
    const ERROR_ANSWER_NOT_SUPPORTED = 16;
    const ZIP_NOT_SUPPORTED = 17;
    const UNEXPECTED_ZIP_FORMAT = 18;
    const FAILED_TO_WRITE_TEMP_FILE = 19;

    public function __construct(string $message = "Unknown error", int $code = self::UNKNOWN_ERROR, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}