<?php

namespace EdituraEDU\ANAF\Responses;

use Exception;
use Throwable;

/**
 * Pseudo response for unifying multiple paged answer lists into a single response
 * @see ANAFAPIClient::ListAnswersWithPagination()
 */
class PagedAnswerListResponse extends ANAFAnswerListResponse
{
    /**
     * Stores a reference to the original responses
     * NOTE: empty responses (no messages) are not stored
     * @var InternalPagedAnswersResponse[] $Pages
     */
    public array $Pages;

    /**
     * Stores a reference to the original errors
     * @var InternalPagedAnswersResponse[] $ErrorPages
     */
    public array $ErrorPages;
    /**
     * Total number of pages
     * @var int $PageCount
     */
    public int $PageCount;

    /**
     * @param InternalPagedAnswersResponse[] $responses
     * @param InternalPagedAnswersResponse[] $errors
     * @return void
     */
    public function __construct(array $responses, array $errors)
    {
        $this->ErrorPages = $errors;
        $this->PageCount = count($responses);
        if ($this->PageCount == 0) {
            $this->Pages = [];
            $this->mesaje = [];
            return;
        }
        $this->Pages = $responses;
        $this->cui = $responses[0]->cui;
        $this->serial = $responses[0]->serial;
        $this->titlu = $responses[0]->titlu;
        $this->mesaje = [];
        if (count($errors) != 0) {
            if (count($errors) == 1) {
                $this->LastError = $errors[0]->LastError;
            } else {
                $this->LastError = new ANAFException("Multiple page requests result in an error", ANAFException::COMPOUND_ERROR);
            }
        }
        for ($i = 0; $i < $this->PageCount; $i++) {
            $this->mesaje = array_merge($this->mesaje, $responses[$i]->mesaje);
        }
    }

    public static function Create($rawResponse): PagedAnswerListResponse
    {
        throw new Exception("This class is not meant to be created from a raw response");
    }

    public static function CreateError(Throwable $error): PagedAnswerListResponse
    {
        $response = new PagedAnswerListResponse([], []);
        $response->LastError = $error;
        return $response;

    }

}