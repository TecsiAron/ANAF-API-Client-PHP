<?php
namespace EdituraEDU\ANAF\Responses;
use DateTime;
use Error;

/**
 * Represents the response structure for @see \EdituraEDU\ANAF\ANAFAPIClient::UploadEFactura()
 */
class UBLUploadResponse extends ANAFAPIResponse
{
    public ?int $ResponseTimestamp = null;
    public function Parse():bool
    {
        if($this->rawResspone===null)
        {
            $this->LastParseError = "No response to parse";
            return false;
        }
        if($this->isErrorResponse($this->rawResspone))
        {
            throw new Error("Unknown message type: $this->rawResspone");
        }
        try
        {
            if ($this->isJson($this->rawResspone)) {
                $this->parseJSON($this->rawResspone);
            } else {
                $this->parseXML($this->rawResspone);
            }
        }
        catch (\Throwable $ex)
        {
            $this->LastParseError = $ex->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Used to check if API response is JSON
     * Answers that are JSON are errors
     * @param string $string
     * @return bool
     */
    private function isJson(string $string): bool {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Used to check if API response is a specific type of error. Can be indicative of a bad request/invalid input data
     * @param string $response
     * @return bool
     */
    private function isErrorResponse(string $response): bool {
        // Check for specific patterns indicative of an error message
        return strpos($response, 'Your support ID is:') !== false;
    }

    /**
     * Parse response XML
     * @param string $xmlString
     * @return void
     */
    private function parseXML(string $xmlString): void {
        $xml = simplexml_load_string($xmlString);
        $dateResponse = (string)$xml['dateResponse'];

        if ($dateResponse) {
            $this->ResponseTimestamp = $this->convertToTimestamp($dateResponse, 'YmdHi');
        }

        $this->success = ((string)$xml['ExecutionStatus'] === "0");

        if (isset($xml['index_incarcare'])) {
            $this->message = (string)$xml['index_incarcare'];
        }
        else if($this->success)
        {
            $this->success=false;
            $this->message="Eroare necunoscută, nu s-a regăsit index_incarcare în răspunsul ANAF";
        }
        if(!$this->success)
        {
            if($xml->Errors && isset($xml->Errors['errorMessage']))
            {
                $this->message = (string)$xml->Errors['errorMessage'];
            }
            else
            {
                $this->message="Eroare necunoscută: $this->rawResspone";
            }
        }
    }

    /**
     * Parse JSON error
     * @param string $jsonString
     * @return void
     */
    private function parseJSON(string $jsonString): void {
        $json = json_decode($jsonString);
        if (isset($json->timestamp)) {
            $this->ResponseTimestamp = $this->convertToTimestamp($json->timestamp, 'd-m-Y H:i:s');
        }

        // JSON response always results in false execution status
        $this->success = false;

        if (isset($json->error) && isset($json->message)) {
            $this->message = $json->error . ": " . $json->message;
        }
    }

    /**
     * Convert date string to timestamp
     * @param string $dateStr
     * @param string $format
     * @return int
     */
    private function convertToTimestamp(string $dateStr, string $format): int {
        $date = DateTime::createFromFormat($format, $dateStr);
        return $date->getTimestamp();
    }
}
