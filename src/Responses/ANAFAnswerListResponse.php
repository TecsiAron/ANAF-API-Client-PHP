<?php

namespace EdituraEDU\ANAF\Responses;

use stdClass;
use Throwable;

/**
 * Represents answer structure for @see \EdituraEDU\ANAF\ANAFAPIClient::ListAnswers()
 */
class ANAFAnswerListResponse extends ANAFResponse
{
    public string $serial;
    public string $cui;
    public string $titlu;
    /**
     * @var ANAFAnswer[]
     */
    public array $mesaje;

    /**
     * @param stdClass $parsed
     * @return void
     */
    private function CopyFromParsed(stdClass $parsed): void
    {
        if (isset($parsed->eroare)) {
            $this->InternalCreateError($parsed->eroare, ANAFException::REMOTE_EXCEPTION);
            return;
        }

        $this->serial = $parsed->serial;
        $this->cui = $parsed->cui;
        $this->titlu = $parsed->titlu;
        $this->mesaje = [];
        foreach ($parsed->mesaje as $mesaj) {
            $this->mesaje[] = ANAFAnswer::CreateFromParsed($mesaj);
        }
    }

    /**
     * Create an error response
     * For internal use!
     * @param Throwable $error
     * @return ANAFAnswerListResponse
     */
    public static function CreateError(Throwable $error): ANAFAnswerListResponse
    {
        $result = new ANAFAnswerListResponse();
        $result->LastError = $error;
        return $result;
    }

    public function Parse(): bool
    {
        try {
            $parsed = $this->CommonParseJSON($this->rawResponse);
            //var_dump($parsed);
            if ($parsed == null && !$this->HasError()) {
                $this->InternalCreateError("Internal error parsing response", ANAFException::UNKNOWN_ERROR);
                return false;
            }
            if(strtolower($parsed->titlu)=="lista mesaje"
                && isset($parsed->eroare)
                && !isset($parsed->mesaje)
                && !isset($parsed->serial)
                && !isset($parsed->cui)
                && str_contains(strtolower($parsed->eroare), "nu exista mesaje"))
            {
                unset($parsed->eroare);
                //var_dump($this->LastError);
                $parsed->mesaje = [];
                $parsed->serial = "";
                $parsed->cui = "";
            }

            $this->CopyFromParsed($parsed);
            return true;
        } catch (\Throwable $ex) {
            $this->LastError = $ex;
            return false;
        }
    }

    public static function Create($rawResponse): ANAFAnswerListResponse
    {
        $response = new ANAFAnswerListResponse();
        $response->rawResponse = $rawResponse;
        $response->Parse();
        return $response;
    }
}