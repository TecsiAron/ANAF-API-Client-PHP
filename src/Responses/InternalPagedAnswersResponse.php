<?php

namespace EdituraEDU\ANAF\Responses;

use EdituraEDU\ANAF\ANAFAPIClient;
use Throwable;

/**
 * Response to the paged answers API endpoint
 * @see ANAFAPIClient::GetAnswerPage()
 */
class InternalPagedAnswersResponse extends ANAFAnswerListResponse
{
    public int $numar_inregistrari_in_pagina = 0;
    public int $numar_total_inregistrari_per_pagina = 0;
    public int $numar_total_inregistrari = 0;
    public int $numar_total_pagini = 0;
    public int $index_pagina_curenta = 0;

    public function Parse(): void
    {
        $pattern = '/Pagina solicitata \d+ este mai mare decat numarul total de pagini \d+/';
        try
        {
            $parsed = $this->CommonParseJSON($this->rawResponse);
        }
        catch (\Throwable $th)
        {
            $this->LastError = $th;
            return;
        }
        if(str_starts_with(strtolower($parsed->titlu), "lista mesaje")
        && isset($parsed->eroare)
        && !isset($parsed->mesaje)
        && !isset($parsed->serial)
        && !isset($parsed->cui)
        && preg_match($pattern, $parsed->eroare))
        {
            $parsed->mesaje = [];
            $parsed->serial = "";
            $parsed->cui = "";
            return;
        }
        parent::Parse();
        if(count($this->mesaje)>0)
        {
            $this->numar_inregistrari_in_pagina = $parsed->numar_inregistrari_in_pagina;
            $this->numar_total_inregistrari_per_pagina = $parsed->numar_total_inregistrari_per_pagina;
            $this->numar_total_inregistrari = $parsed->numar_total_inregistrari;
            $this->numar_total_pagini = $parsed->numar_total_pagini;
            $this->index_pagina_curenta = $parsed->index_pagina_curenta;
        }
    }

    /**
     * Check if the current page is the last page
     * Will always return true if the response is not successful
     * @return bool
     */
    public function IsLastPage():bool
    {
        if($this->IsSuccess())
        {
            return $this->index_pagina_curenta>=$this->numar_total_pagini;
        }
        return true;
    }

    /**
     * Create an error response
     * For internal use!
     * @param Throwable $error
     * @return InternalPagedAnswersResponse
     */
    public static function CreateError(Throwable $error): InternalPagedAnswersResponse
    {
        $result = new InternalPagedAnswersResponse();
        $result->LastError = $error;
        return $result;
    }

    public static function Create($rawResponse): InternalPagedAnswersResponse
    {
        $result = new InternalPagedAnswersResponse();
        $result->rawResponse = $rawResponse;
        $result->Parse();
        return $result;
    }
}