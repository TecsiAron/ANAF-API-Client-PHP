<?php declare(strict_types=1);

namespace EdituraEDU\ANAF\Tests\RequestTests;

class AccessTokenTest extends RequestTestBase
{
    public function testHasAccessToken(): void
    {
        $this->assertFileExists(__DIR__ . '/../../src/ANAFAccessToken.json', "No access token file found");
        $client = $this->createClient();
        $this->assertTrue($client->HasAccessToken(), "Failed to read access token");
    }
}