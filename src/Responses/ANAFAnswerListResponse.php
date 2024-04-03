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
    public bool $success=true;
    public string $titlu;
    public string $eroare = '';
    /**
     * @var ANAFAnswer[]
     */
    public array $mesaje;

    /**
     * Similar to @see Entity::CreateFromParsed
     * @param stdClass $parsed
     * @return ANAFAnswerListResponse
     */
    private function CopyFromParsed(stdClass $parsed):void
    {
        if (isset($parsed->eroare)) {
            $this->InternalCreateError($parsed->eroare, ANAFException::REMOTE_EXCEPTION);
            return;
        }

        $this->serial = $parsed->serial;
        $this->cui = $parsed->cui;
        $this->titlu = $parsed->titlu;
        $this->mesaje = [];
        foreach ($parsed->mesaje as $mesaj)
        {
            $this->mesaje[] = ANAFAnswer::CreateFromParsed($mesaj);
        }
    }

    /**
     * Create an error response
     * For internal use!
     * @param Throwable $error
     * @return ANAFAnswerListResponse
     */
    public static function CreateError(Throwable $error):ANAFAnswerListResponse
    {
        $result = new ANAFAnswerListResponse();
        $result->LastError = $error;
        return $result;
    }

    public function Parse(): bool
    {
        try {
            $parsed = $this->CommonParseJSON($this->rawResponse);
            if ($parsed == null) {
                $this->InternalCreateError("Internal error parsing response", ANAFException::UNKNOWN_ERROR);
                return false;
            }
            $this->CopyFromParsed($parsed);
            return true;
        }
        catch (\Throwable $ex)
        {
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