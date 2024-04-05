<?php

namespace EdituraEDU\ANAF\Responses;

use DateTime;
use Throwable;

/**
 * Represents the response structure for @see \EdituraEDU\ANAF\ANAFAPIClient::UploadEFactura()
 */
class UBLUploadResponse extends ANAFResponse
{
    public ?int $ResponseTimestamp = null;
    public string|null $IndexIncarcare = null;

    public function Parse(): void
    {
        if ($this->rawResponse === null) {
            $this->InternalCreateError("No response to parse", ANAFException::EMPTY_RAW_RESPONSE);
            return;
        }
        if ($this->isErrorResponse($this->rawResponse)) {
            $this->InternalCreateError("API returned an error", ANAFException::REMOTE_EXCEPTION);
            return;
        }
        try {
            if ($this->isJson($this->rawResponse)) {
                $this->parseJSON($this->rawResponse);
            } else {
                $this->parseXML($this->rawResponse);
            }
        } catch (Throwable $ex) {
            $this->LastError = $ex;
        }
    }

    /**
     * Used to check if API response is JSON
     * Answers that are JSON are errors
     * @param string $string
     * @return bool
     */
    private function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Used to check if API response is a specific type of error. Can be indicative of a bad request/invalid input data
     * @param string $response
     * @return bool
     */
    private function isErrorResponse(string $response): bool
    {
        // Check for specific patterns indicative of an error message
        return str_contains($response, 'Your support ID is:');
    }

    /**
     * Parse response XML
     * @param string $xmlString
     * @return void
     */
    private function parseXML(string $xmlString): void
    {
        $xml = simplexml_load_string($xmlString);
        $dateResponse = (string)$xml['dateResponse'];

        if ($dateResponse) {
            $this->ResponseTimestamp = $this->convertToTimestamp($dateResponse, 'YmdHi');
        }

        $success = ((string)$xml['ExecutionStatus'] === "0");

        if (isset($xml['index_incarcare'])) {
            $this->IndexIncarcare = (string)$xml['index_incarcare'];
        } else if ($success) {
            $this->InternalCreateError("Eroare necunoscută, nu s-a regăsit index_incarcare în răspunsul ANAF", ANAFException::INCOMPLETE_RESPONSE);
        }
        if (!$success) {
            if ($xml->Errors && isset($xml->Errors['errorMessage'])) {
                $this->InternalCreateError((string)$xml->Errors['errorMessage'], ANAFException::REMOTE_EXCEPTION);
            } else {
                $this->InternalCreateError("Eroare necunoscută: " . $this->rawResponse, ANAFException::INCOMPLETE_RESPONSE);
            }
        }
    }

    /**
     * Parse JSON error
     * @param string $jsonString
     * @return void
     */
    private function parseJSON(string $jsonString): void
    {
        $json = json_decode($jsonString);
        if (isset($json->timestamp)) {
            $this->ResponseTimestamp = $this->convertToTimestamp($json->timestamp, 'd-m-Y H:i:s');
        }

        // JSON response always results in false execution status
        $message = "Unknown error";

        if (isset($json->error) && isset($json->message)) {
            $message = $json->error . ": " . $json->message;
        }
        $this->InternalCreateError($message, ANAFException::REMOTE_EXCEPTION);
    }

    /**
     * Convert date string to timestamp
     * @param string $dateStr
     * @param string $format
     * @return int
     */
    private function convertToTimestamp(string $dateStr, string $format): int
    {
        $date = DateTime::createFromFormat($format, $dateStr);
        return $date->getTimestamp();
    }

    public static function CreateError(Throwable $error): UBLUploadResponse
    {
        $result = new UBLUploadResponse();
        $result->LastError = $error;
        return $result;
    }
}
