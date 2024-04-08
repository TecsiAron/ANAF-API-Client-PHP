<?php declare(strict_types=1);

namespace EdituraEDU\ANAF\Tests\RequestTests;

use EdituraEDU\ANAF\ANAFAPIClient;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Throwable;

abstract class RequestTestBase extends TestCase
{
    protected int $cif = -1;
    protected const ANAF_OAUTH = [
        'clientId' => 'test-anaf-client-id',
        'clientSecret' => 'test-anaf-client-secret',
        'redirectUri' => 'https://test.com/callback.php',
        'urlAuthorize' => 'https://logincert.anaf.ro/anaf-oauth2/v1/authorize',
        'urlAccessToken' => 'https://logincert.anaf.ro/anaf-oauth2/v1/token',
        'urlResourceOwnerDetails' => 'https://logincert.anaf.ro/anaf-oauth2/v1/resource'
    ];

    protected function setUp(): void
    {
        if (getenv('TEST_ANAF_REQUESTS') !== 'true') {
            $this->markTestSkipped('ANAF requests are disabled');
        } elseif (empty(getenv('TEST_ANAF_CLIENT_CIF'))) {
            $this->fail('TEST_ANAF_CLIENT_CIF is not set');
        } else if (!ANAFAPIClient::ValidateCIF(getenv("TEST_ANAF_CLIENT_CIF"))) {
            $this->fail('Invalid CIF');
        }
        $cif = getenv('TEST_ANAF_CLIENT_CIF');
        $this->setCif($cif);
        parent::setUp();
    }

    private function setCif(string $cif): void
    {
        if (is_numeric($cif)) {
            $this->cif = intval($cif);
        } else {
            if (str_starts_with(strtolower($cif), "ro")) {
                $cif = substr($cif, 2);
                $this->setCif($cif);
            } else {
                $this->fail("Failed to strip CIF: $cif");
            }
        }
    }

    protected function createClient(callable|null $errorCallback = null): ANAFAPIClient
    {
        $classRef = new ReflectionClass(ANAFAPIClient::class);

        if ($errorCallback === null) {
            $errorCallback = function (string $message, ?Throwable $exception = null) {
                if ($exception !== null) {
                    $message .= "\n" . $exception->getMessage() . "\n" . $exception->getTraceAsString();
                }
                $this->fail($message);
            };
        }
        $client = new ANAFAPIClient(self::ANAF_OAUTH, false, $errorCallback, __DIR__ . '/ANAFAccessToken.json');
        $this->assertFalse($client->IsProduction());
        $classRef->getProperty("LockToken")->setValue($client, true);
        return $client;
    }
}