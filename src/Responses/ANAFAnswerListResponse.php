<?php

namespace EdituraEDU\ANAF\Responses;

use stdClass;

class ANAFAnswerListResponse
{
    public string $serial;
    public string $cui;
    public bool $success=true;
    public string $titlu;
    /**
     * @var ANAFAnswer[]
     */
    public array $mesaje;

    public static function CreateFromParsed(stdClass $parsed):ANAFAnswerListResponse
    {
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

    public static function CreateError():ANAFAnswerListResponse
    {
        $response = new ANAFAnswerListResponse();
        $response->success = false;
        return $response;
    }
}