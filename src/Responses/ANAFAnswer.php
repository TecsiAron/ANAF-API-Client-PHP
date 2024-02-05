<?php

namespace EdituraEDU\ANAF\Responses;

/**
 * Represents answer structure for @see ANAFAnswerListResponse::$mesaje
 */
class ANAFAnswer
{
    public string $data_creare;
    public string $cif;
    public string $id_solicitare;
    public string $detalii;
    public string $tip;
    public string $id;

    public static function CreateFromParsed($parsed): ANAFAnswer
    {
        $response = new ANAFAnswer();
        $response->data_creare = $parsed->data_creare;
        $response->cif = $parsed->cif;
        $response->id_solicitare = $parsed->id_solicitare;
        $response->detalii = $parsed->detalii;
        $response->tip = $parsed->tip;
        $response->id = $parsed->id;
        return $response;
    }
}