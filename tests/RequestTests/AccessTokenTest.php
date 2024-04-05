<?php declare(strict_types=1);

namespace EdituraEDU\ANAF\Tests\RequestTests;

use EdituraEDU\ANAF\ANAFAPIClient;

class AccessTokenTest extends RequestRule
{
    public function testHasAccessToken(): void
    {
        $this->assertFileExists(__DIR__ . '/ANAFAccessToken.json', "No access token file found");
        $client = $this->createClient();
        $this->assertTrue($client->HasAccessToken(), "Failed to read access token");
    }
}