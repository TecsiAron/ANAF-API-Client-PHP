<?php

namespace EdituraEDU\ANAF;

use EdituraEDU\ANAF\Responses\ANAFAnswerListResponse;
use EdituraEDU\ANAF\Responses\ANAFVerifyResponse;
use EdituraEDU\ANAF\Responses\EntityResponse;
use EdituraEDU\ANAF\Responses\TVAResponse;
use EdituraEDU\ANAF\Responses\UBLUploadResponse;
use Error;
use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * @license MIT
 * Client for ANAF APIs:
 * - TVA Status
 * - RO EFactura
 */
class ANAFAPIClient extends Client
{
    /**
     * @var float $Timeout Outgoing request timeout in seconds
     */
    public float $Timeout = 5;
    /**
     * @var bool $Production If true, the client will use the production API otherwise will use testing API endpoints
     */
    private bool $Production;
    private ?AccessToken $AccessToken = null;
    /** @var callable|null $ErrorCallback */
    private $ErrorCallback = null;
    private array $OAuthConfig;
    /**
     * @var string $TokenFilePath Path to the file where the access token will be saved/loaded from
     * Specified in @see ANAFAPIClient::__construct()
     */
    private string $TokenFilePath;

    /**
     * @param array $OAuthConfig O Auth config for authenticated requests see README.md
     * @param bool $production If true, the client will use the production API otherwise will use testing API endpoints
     * @param callable|null $errorCallback The callable should have the following signature:
     *                                     function (string $message, ?Throwable $ex = null): void
     * @param string|null $tokenFilePath Path to the file where the access token will be saved/loaded from, if null the file will be called ANAFAccessToken.json in the same folder as this script
     */
    public function __construct(array $OAuthConfig, bool $production, callable $errorCallback = null, ?string $tokenFilePath = null)
    {
        $config['base_uri'] = 'https://webservicesp.anaf.ro';
        $config['headers'] = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $this->Production = $production;
        $this->ErrorCallback = $errorCallback;
        $this->OAuthConfig = $OAuthConfig;
        if ($tokenFilePath == null) {
            $this->TokenFilePath = dirname(__FILE__) . "/ANAFAccessToken.json";
        } else {
            $this->TokenFilePath = $tokenFilePath;
        }
        parent::__construct($config);
    }

    /**
     * Check if a company is registered for TVA
     * @param string $cui
     * @return TVAResponse
     */
    public function CheckTVAStatus(string $cui): TVAResponse
    {
        $cui = trim($cui);
        /**
         * @var TVAResponse $response
         */
        $response = new TVAResponse();
        $sanitizedCUI = $this->SanitizeCUI($cui);
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->DoEntityFetch($sanitizedCUI, $response, $cui);
    }

    /**
     * Get company/institution data from ANAF
     * @param string $cui
     * @return EntityResponse
     */
    public function GetEntity(string $cui): EntityResponse
    {
        $cui = trim($cui);
        $sanitizedCUI = $this->SanitizeCUI($cui);
        $response = new EntityResponse();

        return $this->DoEntityFetch($sanitizedCUI, $response, $cui);
    }

    /**
     * Sanitize CUI input for most requests.
     * Requests to the API should not contain the "RO" prefix and should be numeric.
     * This method is case-insensitive.
     * @param string $cui
     * @return string|false Will return false if the input contains non-numeric other than  the "RO" prefix
     */
    private function SanitizeCUI(string $cui): string|false
    {
        $cui = strtolower($cui);

        if (is_numeric($cui)) {
            return $cui;
        }

        if (str_starts_with($cui, "ro")) {
            $cui = substr($cui, 2);
            return is_numeric($cui) ? $cui : false;
        }

        return false;
    }

    /**
     * Helper method for the initial OAuth2 login
     * @return string
     */
    public function GetLoginURL(): string
    {
        $provider = $this->GetOAuthProvider();
        return $provider->getAuthorizationUrl(['token_content_type' => 'jwt']);
    }

    /**
     * Gets the access token from ANAF, should be called from the OAuth callback script
     * @param string $authCode
     * @return ?AccessToken
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function ProcessOAuthCallback(string $authCode): ?AccessToken
    {
        $provider = $this->GetOAuthProvider();
        $token = $provider->getAccessToken('authorization_code', ["code" => $authCode, 'token_content_type' => 'jwt']);
        try {
            $this->SaveAccessToken($token);
            return $token;
        } catch (Throwable $ex) {
            $this->CallErrorCallback("ANAF token save failed!", $ex);
            return null;
        }
    }

    /**
     * Gets the current access token (if it exists), tries to load it from the file if necessary
     * @return ?AccessToken
     */
    public function GetAccessToken(): ?AccessToken
    {
        if ($this->AccessToken == null) {
            $this->LoadAccessToken();
        }
        return $this->AccessToken;
    }

    /**
     * Saves access token to TokenFilePath
     * @param AccessToken $token
     * @return void
     * @see ANAFAPIClient::$TokenFilePath
     */
    private function SaveAccessToken(AccessToken $token): void
    {
        $tokenJson = json_encode($token);
        file_put_contents($this->TokenFilePath, $tokenJson);
        $this->AccessToken = $token;
    }

    /**
     * Loads the access token from the file specified in TokenFilePath
     * @param bool $autoRefresh if true, the token will be refreshed if it has expired
     * @return AccessToken|null
     * @see ANAFAPIClient::$TokenFilePath
     * Note: This will be called automatically by @see ANAFAPIClient::SendANAFRequest() if the token is not already loaded and the request requires it.
     */
    public function LoadAccessToken(bool $autoRefresh = true): ?AccessToken
    {
        if (file_exists($this->TokenFilePath) === false) {
            return null;
        }

        try {
            $tokenJson = file_get_contents($this->TokenFilePath);
            $token = new AccessToken(json_decode($tokenJson, true));
            $this->AccessToken = $token;

            if ($autoRefresh && $token->hasExpired()) {
                if (!$this->RefreshAccessToken($token)) {
                    $this->CallErrorCallback("ANAF token auto-refresh failed! Will not use expired token!");
                    $this->AccessToken = null;
                }
            }
            return $this->AccessToken;
        } catch (Throwable $ex) {
            $this->CallErrorCallback("ANAF token load failed!", $ex);
            return null;
        }
    }

    /**
     * Refresh the access token using the refresh token
     * @param AccessToken|null $token if null the current access token @see ANAFAPIClient::$AccessToken will be used.
     * @return bool
     */
    public function RefreshAccessToken(?AccessToken $token = null): bool
    {
        $currentToken = $token ?? $this->LoadAccessToken(false);

        if ($currentToken == null) {
            $this->CallErrorCallback("ANAF token refresh failed!");
            return false;
        }

        $provider = $this->GetOAuthProvider();

        try {
            $newToken = $provider->getAccessToken('refresh_token', ["refresh_token" => $currentToken->getRefreshToken()]);
        } catch (Throwable $ex) {
            $this->CallErrorCallback("ANAF token refresh failed!", $ex);
            return false;
        }

        if ($newToken == null || empty($newToken->getToken())) {
            return false;
        }

        $this->SaveAccessToken($newToken);
        return true;
    }

    /**
     * Creates a new instance of the OAuth2 provider based on the configuration specified in the constructor
     * @return GenericProvider
     */
    private function GetOAuthProvider(): GenericProvider
    {
        return new GenericProvider($this->OAuthConfig);
    }

    /**
     * All requests to ANAF are made through this method
     * @param string $Method The URL
     * @param string|null $body The request body (if this is different from null, the request will be a POST request)
     * @param array|null $queryParams Query parameters (if teh value is ["ex1"=>"val"] ?ex1=val will be appended to the URL)
     * @param bool $hasAuth Should be true of the request requires authentication
     * @param string $contentType Content type header for the outgoing request
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function SendANAFRequest(string $Method, ?string $body = null, array|null $queryParams = null, bool $hasAuth = false, string $contentType = "application/json"): ResponseInterface
    {
        $options = ["headers" =>
            [
                "Content-Type" => $contentType,
                "Accept" => "*/*",
                "Cache-Control" => "no-cache",
            ]
        ];

        if ($hasAuth) {
            if (!$this->HasAccessToken()) {
                throw new Error("ANAF API Error: No token");
            }

            $options["headers"]["Authorization"] = "Bearer " . $this->AccessToken->getToken();
        }
        $options["query"] = $queryParams;
        $options["timeout"] = $this->Timeout;

        if ($body === null) {
            return $this->get($Method, $options);
        }

        $options['body'] = $body;
        return $this->post($Method, $options);
    }

    /**
     * Base method for fetching company/institution data from ANAF
     * @param false|string $sanitizedCUI
     * @param EntityResponse $response
     * @param string $cui
     * @return EntityResponse
     */
    public function DoEntityFetch(false|string $sanitizedCUI, EntityResponse $response, string $cui): EntityResponse
    {
        try {
            if ($sanitizedCUI === false || !self::ValidateCIF($sanitizedCUI)) {
                $response->success = false;
                $response->message = "CUI Invalid: $cui";

                return $response;
            }

            $requestBody = [["cui" => $sanitizedCUI, "data" => date("Y-m-d")]];
            $httpResponse = $this->SendANAFRequest("PlatitorTvaRest/api/v8/ws/tva", json_encode($requestBody));

            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300) {
                $response->success = true;
                $content = $httpResponse->getBody()->getContents();
                //var_dump($content);
                $response->rawResspone = $content;

                if (!$response->Parse()) {
                    $response->success = false;
                    $response->message = "Eroare interpretare raspuns ANAF: " . $response->LastParseError . "\n" . $response->rawResspone;
                }

                return $response;
            }
            $response->success = false;
            $response->message = "HTTP Error: " . $httpResponse->getStatusCode();
        } catch (Throwable $ex) {
            $response->success = false;
            $response->message = "Eroare ANAF: " . $ex->getMessage();
            $this->CallErrorCallback("ANAF API Error", $ex);
        }

        return $response;
    }

    /**
     * @param string $ubl UBL XML content to upload
     * @param string $sellerCIF CUI of the company which issued the invoice
     * @param bool $extern If true, the invoice is marked as "extern" (not issued to a romanian company)
     * @param bool $autoFactura If true, the invoice is marked as "autofactura" (issued by the buyer in the sellers name)
     * @return UBLUploadResponse
     */
    public function UploadEFactura(string $ubl, string $sellerCIF, bool $extern = false, bool $autoFactura = false): UBLUploadResponse
    {
        $response = new UBLUploadResponse();

        try {
            $modeName = $this->Production ? "prod" : "test";
            $method = "https://api.anaf.ro/$modeName/FCTEL/rest/upload";
            $queryParams = ["standard" => "UBL", "cif" => $sellerCIF];

            if ($extern) {
                $queryParams["extern"] = "DA";
            }

            if ($autoFactura) {
                $queryParams["autofactura"] = "DA";
            }

            $httpResponse = $this->SendANAFRequest($method, $ubl, $queryParams, true);

            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300) {
                //var_dump($httpResponse);
                $content = $httpResponse->getBody()->getContents();
                $response->rawResspone = $content;

                if (!$response->Parse()) {
                    $response->success = false;
                    $response->message = "Eroare interpretare raspuns ANAF: " . $response->LastParseError;
                }
            }
        } catch (Throwable $ex) {
            $response->success = false;
            $response->message = "Eroare ANAF: " . $ex->getMessage();
            $this->CallErrorCallback("ANAF API Error", $ex);
        }

        return $response;
    }

    /**
     * Download Answer from ANAF
     * To obtain the ID, use ListAnswers
     * @param string $id
     * @return string either an error message (starts with "ERROR_") or zip file content
     * @see ANAFAPIClient::ListAnswers()
     */
    public function DownloadAnswer(string $id): string
    {
        $modeName = $this->Production ? "prod" : "test";
        $method = "https://api.anaf.ro/$modeName/FCTEL/rest/descarcare?id=$id";

        try {
            $httpResponse = $this->SendANAFRequest($method, null, null, true);

            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300) {
                //var_dump($httpResponse);
                $content = $httpResponse->getBody()->getContents();

                if (str_starts_with($content, "PK")) {
                    return $content;
                }
                return "ERROR_BAD_CONTENT";

            }
        } catch (Throwable $ex) {
            $this->CallErrorCallback("ANAF API Error", $ex);
            return "ERROR";
        }

        return "ERROR_NO_RESPONSE";
    }

    public function UBL2PDF(string $ubl, string $metadata): string|false
    {
        if (str_contains($ubl, 'xsi:schemaLocation')) {
            $ubl = $this->RemoveSchemaLocationAttribute($ubl);
        }

        $modeName = $this->Production ? "prod" : "test";
        $method = "https://webservicesp.anaf.ro/$modeName/FCTEL/rest/transformare/FACT1/DA";

        try {
            $httpResponse = $this->SendANAFRequest($method, $ubl, null, false, "text/plain");

            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300) {
                //var_dump($httpResponse);
                $content = $httpResponse->getBody()->getContents();
                if (str_starts_with($content, "%PDF")) {
                    return $content;
                }
                $this->CallErrorCallback("Bad content format, expected PDF (UBL2PDF) - $metadata");
                $this->CallErrorCallback($content);

                return $content;

            }
        } catch (Throwable $ex) {
            $this->CallErrorCallback("ANAF API Error", $ex);
            return false;
        }

        return false;
    }

    /**
     * Removes the xsi:schemaLocation attribute from the root element of the UBL XML
     * @param $xmlString
     * @return string
     */
    private function RemoveSchemaLocationAttribute($xmlString): string {
        $pattern = '/(xsi:schemaLocation\s*=\s*["\'][^"\']*["\'])/i';
        $xmlString = preg_replace($pattern, '', $xmlString, -1, $count);
        $xmlString = preg_replace('/\s{2,}/', ' ', $xmlString);
        return $xmlString;
    }

    /**
     * Get answer list for a company (authenticated user must have access to the company!)
     * @param int $cif
     * @param int $days Number of days to look back
     * @return ANAFAnswerListResponse
     */
    public function ListAnswers(int $cif, int $days = 60): ANAFAnswerListResponse
    {
        $modeName = $this->Production ? "prod" : "test";
        $method = "https://api.anaf.ro/$modeName/FCTEL/rest/listaMesajeFactura?zile=$days&cif=$cif";

        try {
            $httpResponse = $this->SendANAFRequest($method, null, null, true);

            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300) {
                //var_dump($httpResponse);
                $content = $httpResponse->getBody()->getContents();
                return ANAFAnswerListResponse::CreateFromParsed(json_decode($content));
            }
        } catch (Throwable $ex) {
            $this->CallErrorCallback("ANAF API Error", $ex);
            return ANAFAnswerListResponse::CreateError($ex->getMessage());
        }

        return ANAFAnswerListResponse::CreateError();
    }

    /**
     * ATTENTION DOES NOT FUNCTION RELIABLY.
     * The API randomly returns "nok" for valid invoices.
     * @param string $ubl
     * @return bool
     * @deprecated Use at your own risk. Read the comment above.
     */
    public function VerifyXML(string $ubl): bool
    {
        try {
            $method = "https://webservicesp.anaf.ro/prod/FCTEL/rest/validare/FACT1";
            $httpResponse = $this->SendANAFRequest($method, $ubl, null, false, "text/plain");
            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300) {
                //var_dump($httpResponse);
                $contentString = $httpResponse->getBody()->getContents();
                $content = ANAFVerifyResponse::CreateFromParsed(json_decode($contentString));
                return $content->IsOK();
            }
        } catch (Throwable $ex) {
            $this->CallErrorCallback("ANAF API Error", $ex);
            return false;
        }

        $this->CallErrorCallback("ANAF VERIFY ERROR: NO RESPONSE OR ERROR");

        return false;
    }

    /**
     * Calls ErrorCallback with given params.
     * @param string $message
     * @param Throwable|null $ex
     * @return void
     * @see          ANAFAPIClient::$ErrorCallback
     */
    private function CallErrorCallback(string $message, ?Throwable $ex = null): void
    {
        if ($this->ErrorCallback != null) {
            ($this->ErrorCallback)($message, $ex);
            return;
        }

        error_log($message);

        if ($ex != null) {
            error_log($ex->getMessage());
            error_log($ex->getTraceAsString());
        }
    }

    /**
     * Check if the client has a valid access token.
     */
    public function HasAccessToken(): bool
    {
        return $this->GetAccessToken() != null;
    }

    /**
     * @return bool
     * @see ANAFAPIClient::$Production
     */
    public function IsProduction(): bool
    {
        return $this->Production;
    }

    /**
     * Verifies a CUI given the official checksum algorithm
     * @param string|false $cif
     * @return bool
     */
    public static function ValidateCIF(string|false $cif): bool
    {
        if ($cif === false) {
            return false;
        }
        if (!is_int($cif)) {
            $cif = strtoupper($cif);
            if (strpos($cif, 'RO') === 0) {
                $cif = substr($cif, 2);
            }
            $cif = (int)trim($cif);
        }

        if (strlen($cif) > 10 || strlen($cif) < 2) {
            return false;
        }
        $v = 753217532;
        $c1 = $cif % 10;
        $cif = (int)($cif / 10);
        $t = 0;
        while ($cif > 0) {
            $t += ($cif % 10) * ($v % 10);
            $cif = (int)($cif / 10);
            $v = (int)($v / 10);
        }
        $c2 = $t * 10 % 11;
        if ($c2 == 10) {
            $c2 = 0;
        }
        return $c1 === $c2;
    }
}
