<?php

namespace EdituraEDU\ANAF\Responses;

use stdClass;

/**
 * Represents answer structure for @see \EdituraEDU\ANAF\ANAFAPIClient::ListAnswers()
 */
class ANAFAnswerListResponse
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
    public static function CreateFromParsed(stdClass $parsed):ANAFAnswerListResponse
    {
        if (isset($parsed->eroare)) {
            return self::CreateError($parsed->eroare);
        }

        $response = new ANAFAnswerListResponse();
        $response->serial = $parsed->serial;
        $response->cui = $parsed->cui;
        $response->titlu = $parsed->titlu;
        $response->mesaje = [];
        foreach ($parsed->mesaje as $mesaj)
        {
            $response->mesaje[] = ANAFAnswer::CreateFromParsed($mesaj);
        }
        return $response;
    }

    /**
     * Create an error response
     * For internal use!
     * @param string $error optional error message
     * @return ANAFAnswerListResponse
     */
    public static function CreateError(string $error = ''):ANAFAnswerListResponse
    {
        $response = new ANAFAnswerListResponse();
        $response->success = false;
        $response->eroare = $error;
        return $response;
    }
}