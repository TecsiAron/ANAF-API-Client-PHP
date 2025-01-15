<?php

namespace EdituraEDU\ANAF;

use EdituraEDU\ANAF\Responses\ANAFAnswerListResponse;
use EdituraEDU\ANAF\Responses\ANAFException;
use EdituraEDU\ANAF\Responses\ANAFVerifyResponse;
use EdituraEDU\ANAF\Responses\EntityResponse;
use EdituraEDU\ANAF\Responses\InternalPagedAnswersResponse;
use EdituraEDU\ANAF\Responses\PagedAnswerListResponse;
use EdituraEDU\ANAF\Responses\TVAResponse;
use EdituraEDU\ANAF\Responses\UBLUploadResponse;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
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
class ANAFAPIClient
{
    /**
     * @var float $Timeout Outgoing request timeout in seconds
     */
    public float $Timeout = 5;
    /**
     * @var bool $Production If true, the client will use the production API otherwise will use testing API endpoints
     */
    private bool $Production;
    protected ?AccessToken $AccessToken = null;
    /** @var callable|null $ErrorCallback */
    private $ErrorCallback = null;
    private array $OAuthConfig;
    /**
     * @var string $TokenFilePath Path to the file where the access token will be saved/loaded from
     * Specified in @see ANAFAPIClient::__construct()
     */
    private string $TokenFilePath;
    /**
     * @var Client $PublicAPIClient Guzzle Client for public API requests
     */
    private Client $PublicAPIClient;
    /**
     * @var Client $AuthenticatedAPIClient Guzzle Client for authenticated API requests
     */
    private Client $AuthenticatedAPIClient;
    /**
     * @var bool $LockToken Used to make sure test suit does not mutate the token
     */
    private bool $LockToken = false;

    /**
     * @param array $OAuthConfig O Auth config for authenticated requests see README.md
     * @param bool $production If true, the client will use the production API otherwise will use testing API endpoints
     * @param callable|null $errorCallback The callable should have the following signature:
     *                                     function (string $message, ?Throwable $ex = null): void
     * @param string|null $tokenFilePath Path to the file where the access token will be saved/loaded from, if null the file will be called ANAFAccessToken.json in the same folder as this script
     */
    public function __construct(array $OAuthConfig, bool $production, callable $errorCallback = null, ?string $tokenFilePath = null)
    {
        $config = [];
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
        $this->PublicAPIClient = new Client($config);
        $config = [];
        $config['base_uri'] = 'https://api.anaf.ro';
        $config['headers'] = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $this->AuthenticatedAPIClient = new Client($config);
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
         * @noinspection PhpRedundantVariableDocTypeInspection
         */
        $response = new TVAResponse();
        $sanitizedCUI = $this->SanitizeCUI($cui);
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->DoEntityFetch($sanitizedCUI, $response);
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

        return $this->DoEntityFetch($sanitizedCUI, $response);
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
     * @throws IdentityProviderException
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
        if ($this->LockToken) {
            return;
        }
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

            if ($autoRefresh && $this->TokenWillExpireSoon()) {
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
     * Checks if the current access token will expire soon
     * @param int $soon Time in seconds to consider "soon" Default value is 24 hours
     * @return bool|null Null if the token is not set or does not have an expiration date
     */
    public function TokenWillExpireSoon(int $soon = (3600 * 24)): bool|null
    {
        if ($this->AccessToken == null) {
            return null;
        }
        $expires = $this->AccessToken->getExpires();
        if ($expires == null) {
            return null;
        }
        return $expires < time() + $soon;
    }

    /**
     * Refresh the access token using the refresh token
     * @param AccessToken|null $token if null the current access token @see ANAFAPIClient::$AccessToken will be used.
     * @return bool
     */
    public function RefreshAccessToken(?AccessToken $token = null): bool
    {
        if ($this->LockToken) {
            return true;
        }
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
     * @param float|null $timeoutOverride if set, will override the default timeout (ANAFAPIClient::$Timeout)
     * @return ResponseInterface
     * @throws GuzzleException
     */
    private function SendANAFRequest(string $Method, ?string $body = null, array|null $queryParams = null, bool $hasAuth = false, string $contentType = "application/json", float|null $timeoutOverride = null): ResponseInterface
    {
        $client = $hasAuth ? $this->AuthenticatedAPIClient : $this->PublicAPIClient;
        $options = ["headers" =>
            [
                "Content-Type" => $contentType,
                "Accept" => "*/*",
                "Cache-Control" => "no-cache",
            ]
        ];

        if ($hasAuth) {
            if (!$this->HasAccessToken()) {
                throw new Exception("ANAF API Error: No token");
            }

            $options["headers"]["Authorization"] = "Bearer " . $this->AccessToken->getToken();
        }
        $options["query"] = $queryParams;
        $options["timeout"] = $timeoutOverride === null ? $this->Timeout : $timeoutOverride;

        if ($body === null) {
            return $client->get($Method, $options);
        }

        $options['body'] = $body;
        return $client->post($Method, $options);
    }

    /**
     * Base method for fetching company/institution data from ANAF
     * @param false|string $sanitizedCUI
     * @param EntityResponse $response
     * @return EntityResponse
     */
    public function DoEntityFetch(false|string $sanitizedCUI, EntityResponse $response): EntityResponse
    {
        try {
            if ($sanitizedCUI === false || !self::ValidateCIF($sanitizedCUI)) {
                $response->LastError = new ANAFException("CUI invalid", ANAFException::INVALID_INPUT);
                return $response;
            }

            $requestBody = [["cui" => $sanitizedCUI, "data" => date("Y-m-d")]];
            $httpResponse = $this->SendANAFRequest("PlatitorTvaRest/api/v8/ws/tva", json_encode($requestBody));

            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300) {
                $content = $httpResponse->getBody()->getContents();
                //var_dump($content);
                $response->rawResponse = $content;
                $response->Parse();
                return $response;
            }
            $response->LastError = new ANAFException("HTTP Error: " . $httpResponse->getStatusCode(), ANAFException::HTTP_ERROR);
        } catch (Throwable $ex) {
            $response->LastError = $ex;
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
            $method = "/$modeName/FCTEL/rest/upload";
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
                $response->rawResponse = $content;
                $response->Parse();

            }
        } catch (Throwable $ex) {
            $response->LastError = $ex;
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
        $method = "/$modeName/FCTEL/rest/descarcare?id=$id";

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

    /**
     * Convert UBL XML to PDF using the ANAF API
     * @param string $ubl
     * @param string $metadata
     * @param bool $authenticated if true the request will use the OAuth2 API endpoint
     * @param float|null $timeoutOverride if set, will override the default timeout (ANAFAPIClient::$Timeout)
     * @return string|false
     */
    public function UBL2PDF(string $ubl, string $metadata, bool $authenticated = true, float|null $timeoutOverride = null): string|false
    {
        if (str_contains($ubl, 'xsi:schemaLocation')) {
            $ubl = self::RemoveSchemaLocationAttribute($ubl);
        }
        $method = "/prod/FCTEL/rest/transformare/FACT1/DA";
        try {
            $timeoutOverride = $timeoutOverride ?? $this->Timeout;
            $httpResponse = $this->SendANAFRequest($method, $ubl, null, $authenticated, "text/plain", $timeoutOverride);
            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300) {
                $content = $httpResponse->getBody()->getContents();
                if (str_starts_with($content, "%PDF")) {
                    return $content;
                }
                $this->CallErrorCallback("Bad content format, expected PDF (UBL2PDF) - $metadata");
                $this->CallErrorCallback($content);
                return false;

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
    public static function RemoveSchemaLocationAttribute($xmlString): string
    {
        $pattern = '/(xsi:schemaLocation\s*=\s*["\'][^"\']*["\'])/i';
        $xmlString = preg_replace($pattern, '', $xmlString, -1);
        return preg_replace('/\s{2,}/', ' ', $xmlString);
    }

    /**
     * Get answer list for a company (authenticated user must have access to the company!)
     * @param int $cif
     * @param int $days Number of days to look back
     * @param string|null $filter
     * @param bool $usePaginationIfNeeded If true and the answer has LastError code ANAFException::MESSAGE_LIST_TOO_LONG, the method will try to fetch the answers using pagination
     * @param float|int $endDateOffsetForPagination ANAF API gives an error if the end date is "in the future" based on their internal clock, so end date will be currentTime-$endDateOffsetForPagination. Default is 5 minutes
     * @return ANAFAnswerListResponse
     * @see ANAFAPIClient::ListAnswersWithPagination()
     */
    public function ListAnswers(int $cif, int $days = 60, string|null $filter = null, bool $usePaginationIfNeeded = false, $endDateOffsetForPagination = 5 * 60): ANAFAnswerListResponse
    {
        if ($filter != null) {
            $filter = strtoupper($filter);
            if (!$this->ValidateFilter($filter)) {
                return ANAFAnswerListResponse::CreateError(new ANAFException("Invalid filter", ANAFException::INVALID_INPUT));
            }
        }
        $modeName = $this->Production ? "prod" : "test";
        $method = "/$modeName/FCTEL/rest/listaMesajeFactura?zile=$days&cif=$cif";
        if ($filter != null) {
            $method .= "&filtru=$filter";
        }
        try {
            $httpResponse = $this->SendANAFRequest($method, null, null, true);

            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300) {
                //var_dump($httpResponse);
                $content = $httpResponse->getBody()->getContents();
                $answer = ANAFAnswerListResponse::Create($content);
                if ($answer->IsSuccess() || !$usePaginationIfNeeded) {
                    return $answer;
                }
                if ($answer->LastError->getCode() == ANAFException::MESSAGE_LIST_TOO_LONG) {
                    $startDate = time() - ($days * 24 * 3600);
                    $endDate = time() - $endDateOffsetForPagination;
                    return $this->ListAnswersWithPagination($startDate, $endDate, $cif, null, $filter);
                }
            }
        } catch (Throwable $ex) {
            $this->CallErrorCallback("ANAF API Error", $ex);
            return ANAFAnswerListResponse::CreateError($ex);
        }

        return ANAFAnswerListResponse::CreateError(new ANAFException("No response or error", ANAFException::UNKNOWN_ERROR));
    }

    /**
     * Get answer list for a company (authenticated user must have access to the company!)
     * Uses the paged API end point
     * Answers will be returned in a unified list
     * @param int $startTime
     * @param int $endTime
     * @param int $cif
     * @param int|null $specificPage
     * @param string|null $filter
     * @return PagedAnswerListResponse
     */
    public function ListAnswersWithPagination(int $startTime, int $endTime, int $cif, int $specificPage = null, string|null $filter = null): PagedAnswerListResponse
    {
        if ($filter != null) {
            $filter = strtoupper($filter);
            if (!$this->ValidateFilter($filter)) {
                return PagedAnswerListResponse::CreateError(new ANAFException("Invalid filter", ANAFException::INVALID_INPUT));
            }
        }
        if ($specificPage != null) {
            $response = $this->GetAnswerPage($startTime, $endTime, $cif, $specificPage, $filter);
            if ($response->IsSuccess()) {
                return new PagedAnswerListResponse([$response], []);
            }
            return PagedAnswerListResponse::CreateError($response->LastError);
        }
        /**
         * @var InternalPagedAnswersResponse[] $pages
         */
        $pages = [];
        $errors = [];
        $currentPage = 1;
        $canFetchNextPage = true;
        while ($canFetchNextPage) {
            $response = $this->GetAnswerPage($startTime, $endTime, $cif, $currentPage, $filter);
            if ($response->IsSuccess()) {
                if (count($response->mesaje) > 0) {
                    $pages[] = $response;
                }
            } else {
                $errors[] = $response;
            }
            $canFetchNextPage = !$response->IsLastPage();
            $currentPage++;
        }

        return new PagedAnswerListResponse($pages, $errors);
    }

    /**
     * Call the paged answer list endpoint with a speicifc page number
     * @param int $startTime
     * @param int $endTime
     * @param int $cif
     * @param int $pageNumber
     * @param string|null $filter
     * @return InternalPagedAnswersResponse
     */
    public function GetAnswerPage(int $startTime, int $endTime, int $cif, int $pageNumber, string|null $filter = null): InternalPagedAnswersResponse
    {
        $modeName = $this->Production ? "prod" : "test";
        $actualStart = $startTime * 1000;
        $actualEnd = $endTime * 1000;
        $method = "/$modeName/FCTEL/rest/listaMesajePaginatieFactura?startTime=$actualStart&endTime=$actualEnd&cif=$cif&pagina=$pageNumber";
        if ($filter != null) {
            $method .= "&filtru=$filter";
        }
        try {
            $httpResponse = $this->SendANAFRequest($method, null, null, true);

            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300) {
                //var_dump($httpResponse);
                $content = $httpResponse->getBody()->getContents();
                return InternalPagedAnswersResponse::Create($content);
            }
        } catch (Throwable $ex) {
            $this->CallErrorCallback("ANAF API Error", $ex);
            return InternalPagedAnswersResponse::CreateError($ex);
        }

        return InternalPagedAnswersResponse::CreateError(new ANAFException("No response or error", ANAFException::UNKNOWN_ERROR));

    }

    /**
     * ATTENTION DOES NOT FUNCTION RELIABLY.
     * The API randomly returns "nok" for valid invoices.
     * @param string $ubl
     * @param bool $authenticated If true, the request will use the OAuth2 API endpoint
     * @return ANAFVerifyResponse
     * @deprecated Use at your own risk. Read the comment above.
     */
    public function VerifyXML(string $ubl, bool $authenticated = true): ANAFVerifyResponse
    {
        try {
            $method = "/prod/FCTEL/rest/validare/FACT1";
            $httpResponse = $this->SendANAFRequest($method, $ubl, null, $authenticated, "text/plain");
            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300) {
                //var_dump($httpResponse);
                $contentString = $httpResponse->getBody()->getContents();
                return ANAFVerifyResponse::Create($contentString);
            }
        } catch (Throwable $ex) {
            return ANAFVerifyResponse::CreateError($ex);
        }

        $this->CallErrorCallback("ANAF VERIFY ERROR: NO RESPONSE OR ERROR");
        return ANAFVerifyResponse::CreateError(new ANAFException("No response or error", ANAFException::UNKNOWN_ERROR));
    }

    /**
     * Currently accepted filter chars are: E, T, P, R
     * @param string $filter
     * @return bool
     */
    private function ValidateFilter(string $filter): bool
    {
        if (strlen($filter) != 1) {
            return false;
        }
        $acceptedFilters = ["E", "T", "P", "R"];
        return in_array($filter, $acceptedFilters);
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
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (!is_int($cif)) {
            $cif = strtoupper($cif);
            if (str_starts_with($cif, 'RO')) {
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
