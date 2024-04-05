<?php

namespace EdituraEDU\ANAF\Tests\RequestTests;

use Throwable;

class AnswerListAnswerTest extends RequestRule
{
    /**
     *  @requires testHasAccessToken
     */
    public function testEmptyResponse()
    {
        try {
            $client = $this->createClient();
            $response = $client->ListAnswers($this->cif,1);
            $this->assertFalse($response->IsSuccess(), "Response is successful");
            $this->assertIsString($response->LastError->getMessage(), "Error message is not a string");
        } catch (Throwable $ex) {
            $this->fail("Exception thrown: " . $ex->getMessage());
        }
    }

}