<?php
namespace EdituraEDU\ANAF\Responses;
use Exception;
use Throwable;

class PagedAnswerListResponse extends ANAFAnswerListResponse
{
    public array $Pages;
    public int $PageCount;
    /**
     * @param InternalPagedAnswersResponse[] $responses
     * @return void
     */
    public function __construct(array $responses)
    {
        $this->PageCount=count($responses);
        if($this->PageCount==0)
        {
            $this->Pages=[];
            $this->mesaje=[];
            return;
        }
        $this->Pages = $responses;
        $this->cui=$responses[0]->cui;
        $this->serial=$responses[0]->serial;
        $this->titlu=$responses[0]->titlu;
        $this->mesaje=[];
        for($i=0; $i<$this->PageCount; $i++)
        {
            $this->mesaje=array_merge($this->mesaje,$responses[$i]->mesaje);
        }
    }

    /**
     * Overrides the default HasError to check all pages for errors if the current instance has no error of its own
     * @return bool
     */
    public function HasError(): bool
    {
        if($this->LastError!=null)
        {
            return true;
        }
        foreach($this->Pages as $page)
        {
            if($page->HasError())
            {
                return true;
            }
        }
        return false;
    }

    /**
     * May not be needed?
     * @return bool
     */
    public function IsSuccess(): bool
    {
        return !$this->HasError();
    }

    public static function Create($rawResponse): PagedAnswerListResponse
    {
        throw new Exception("This class is not meant to be created from a raw response");
    }

    public static function CreateError(Throwable $error): PagedAnswerListResponse
    {
        $response=new PagedAnswerListResponse([]);
        $response->LastError=$error;
        return $response;

    }

}