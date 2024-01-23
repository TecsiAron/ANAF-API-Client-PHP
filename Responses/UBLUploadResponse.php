<?php
namespace EdituraEDU\Admin\ANAF\Responses;
use DateTime;
use Error;

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

    private function isJson(string $string): bool {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    private function isErrorResponse(string $response): bool {
        // Check for specific patterns indicative of an error message
        return strpos($response, 'Your support ID is:') !== false;
    }

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

    private function convertToTimestamp(string $dateStr, string $format): int {
        $date = DateTime::createFromFormat($format, $dateStr);
        return $date->getTimestamp();
    }
}
