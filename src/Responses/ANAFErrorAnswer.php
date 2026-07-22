<?php

namespace EdituraEDU\ANAF\Responses;

use EdituraEDU\ANAF\ANAFAPIClient;
use LibXMLError;
use Throwable;

/**
 * Used for error answers found during DownloadAnswer
 * @see ANAFAPIClient::DownloadAnswer()
 * credit to https://github.com/octapopa
 */
class ANAFErrorAnswer extends ANAFResponse
{
    public string $AnswerID;
    public string|null $message = null;
    public string|null $index_incarcare = null;

    public string|null $data_incarcare = null;

    public string|null $cif_emitent = null;


    public function Parse(): void
    {
        if (!self::IsSupported()) {
            $this->InternalCreateError("simplexml_load_string() or libxml_use_internal_errors() not found.", ANAFException::ERROR_ANSWER_NOT_SUPPORTED);
        }
        try {
            $xml = $this->rawResponse;
            libxml_use_internal_errors(true);
            $parsedXML = simplexml_load_string($xml);
            if ($parsedXML === false) {
                $libXMLErrors = libxml_get_errors();
                $errorDescriptions = [];
                for ($i = 0; $i < count($libXMLErrors); $i++) {
                    if ($libXMLErrors[$i] instanceof LibXMLError) {
                        $errorDescriptions[] = $libXMLErrors[$i]->message;
                    } else {
                        $errorDescriptions[] = "Anomalous error: " . json_encode($libXMLErrors[$i]);
                    }
                }
                $this->InternalCreateError("Error parsing XML for answer $this->AnswerID: " . implode(", ", $errorDescriptions), ANAFException::ERROR_ANSWER_PARSE_FAILED);
                return;
            }

            // Dacă este o factură validă (Invoice UBL), returnează mesaj de succes
            $rootName = strtolower($parsedXML->getName());
            if ($rootName === 'invoice') {
                $this->InternalCreateError("Was expecting error, got valid invoice (Answer ID: $this->AnswerID).", ANAFException::EXPECTED_ERROR_GOT_VALID_ANSWER);
                return;
            }

            // Extrage namespace și atributele header-ului
            $namespaces = $parsedXML->getDocNamespaces(true);
            $header = $parsedXML;
            $attrs = $header->attributes();
            if (isset($attrs['Index_incarcare'])) {
                $this->index_incarcare = (string)$attrs['Index_incarcare'];
            }
            if (isset($attrs['Cif_emitent'])) {
                $this->cif_emitent = (string)$attrs['Cif_emitent'];
            }

            // Caută <Error> sau <Eroare>
            $errorNode = null;
            if (!empty($namespaces)) {
                $prefix = array_key_first($namespaces);
                foreach ($header->children($prefix ?: null) as $child) {
                    $name = strtolower($child->getName());
                    if ($name === 'error' || $name === 'eroare') {
                        $errorNode = $child;
                        break;
                    }
                }
            } else {
                foreach ($header->children() as $child) {
                    $name = strtolower($child->getName());
                    if ($name === 'error' || $name === 'eroare') {
                        $errorNode = $child;
                        break;
                    }
                }
            }

            if ($errorNode) {
                $errorAttrs = $errorNode->attributes($ns ?? null);
                if (isset($errorAttrs['errorMessage'])) {
                    $this->message = (string)$errorAttrs['errorMessage'];
                    if (preg_match('/index=([0-9]+)\s+si\s+data incarcare=([0-9\-]+)/i', $this->message, $m)) {
                        if (empty($this->index_incarcare)) {
                            $this->index_incarcare = $m[1];
                        }
                        $this->data_incarcare = $m[2];
                    }
                } elseif ((string)$errorNode) {
                    $this->message = (string)$errorNode;
                }
            }
        } catch (Throwable $ex) {
            $this->InternalCreateError("Error parsing XML for answer $this->AnswerID: " . $ex->getMessage(), ANAFException::UNKNOWN_ERROR, $ex);
            $this->message = $ex->getMessage();
            $this->error = true;
        }
    }

    public function IsDuplicateUploadError(): bool
    {
        if (!$this->IsSuccess() || empty($this->message)) return false;
        return str_starts_with(strtolower($this->message), "factura a mai fost transmisa anterior");
    }

    /**
     * Creează un obiect de tip ANAFDownloadedFileError dintr-un răspuns XML.
     * @param string $rawResponse
     * @return ANAFErrorAnswer
     */
    public static function Create(string $id, string $rawResponse): self
    {
        $response = new ANAFErrorAnswer();
        $response->AnswerID = $id;
        $response->rawResponse = $rawResponse;
        $response->Parse();
        return $response;
    }

    /**
     * Creează un obiect de tip ANAFDownloadedFileError dintr-o excepție.
     * @param Throwable $error
     * @return ANAFErrorAnswer
     */
    public static function CreateError(Throwable $error): self
    {
        $response = new ANAFErrorAnswer();
        $response->message = $error->getMessage();
        $response->LastError = $error;
        $response->AnswerID = "UNKNOWN";
        return $response;
    }

    /**
     * Checks for SimpleXML and libxml support in the current PHP environment.
     * @return bool
     */
    public static function IsSupported(): bool
    {
        return function_exists('simplexml_load_string') && function_exists('libxml_use_internal_errors');
    }

}