<?php

namespace EdituraEDU\ANAF;

use DOMDocument;
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
    private ?AccessToken $AccessToken=null;
    /** @var callable|null $ErrorCallback */
    private $ErrorCallback=null;
    private array $OAuthConfig;

    /**
     * @param array $OAuthConfig O Auth config for authenticated requests see README.md
     * @param bool $production If true, the client will use the production API otherwise will use testing API endpoints
     * @param callable|null $errorCallback See example.php
     */
    public function __construct(array $OAuthConfig, bool $production, callable $errorCallback=null)
    {
        $config['base_uri'] = 'https://webservicesp.anaf.ro';
        $config['headers'] = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $this->Production = $production;
        $this->ErrorCallback=$errorCallback;
        $this->OAuthConfig=$OAuthConfig;
        parent::__construct($config);
    }

    /**
     * Check if a company is registered for TVA
     * @param string $cui
     * @return TVAResponse
     */
    public function CheckTVAStatus(string $cui): TVAResponse
    {
        $cui=trim($cui);
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
    public function GetEntity(string $cui):EntityResponse
    {
        $cui=trim($cui);
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
        if (is_numeric($cui))
        {
            return $cui;
        }
        if (str_starts_with($cui, "ro"))
        {
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
     * Gets the access token from ANAF
     * @param string $authCode
     * @return AccessToken
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function GetAccessToken(string $authCode): AccessToken
    {
        $provider = $this->GetOAuthProvider();
        return $provider->getAccessToken('authorization_code', ["code" => $authCode, 'token_content_type' => 'jwt']);
    }

    /**
     * Saves access token to the same directory as this script (ANAFAccessToken.json)
     * @TODO make this configurable
     * @param AccessToken $token
     * @return void
     */
    public function SaveAccessToken(AccessToken $token):void
    {
        $tokenJson = json_encode($token);
        file_put_contents(dirname(__FILE__) . "/ANAFAccessToken.json", $tokenJson);
    }

    /**
     * Loads access token from the same directory as this script (ANAFAccessToken.json) will return null if the file is not found or the token is invalid
     * @return AccessToken|null
     */
    public function LoadAccessToken(): ?AccessToken
    {
        try
        {
            $tokenJson = file_get_contents(dirname(__FILE__) . "/ANAFAccessToken.json");
            $token = json_decode($tokenJson, true);
            return new AccessToken($token);
        } catch (Throwable $ex)
        {
             error_log("ANAF API Failed to load token:" . $ex->getMessage()."\n".$ex->getTraceAsString());
            return null;
        }
    }

    /**
     * Refresh the access token using the refresh token
     * @return bool
     */
    public function RefreshToken(): bool
    {
        $currentToken = $this->LoadAccessToken();
        if ($currentToken == null)
        {
            $this->CallErrorCallback("ANAF token refresh failed!");
            return false;
        }
        $provider = $this->GetOAuthProvider();
        try
        {
            $newToken = $provider->getAccessToken('refresh_token', ["refresh_token" => $currentToken->getRefreshToken()]);
        } catch (Throwable $ex)
        {
            $this->CallErrorCallback("ANAF token refresh failed!");
            error_log("ANAF OAuth refresh failed: " . $ex->getMessage());
            return false;
        }
        if($newToken==null || empty($newToken->getToken()))
        {
            return  false;
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
     * @param string|null $body  The request body (if this is different from null, the request will be a POST request)
     * @param array|null $queryParams Query parameters (if teh value is ["ex1"=>"val"] ?ex1=val will be appended to the URL)
     * @param bool $hasAuth Should be true of the request requires authentication
     * @param string $contentType Content type header for the outgoing request
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function SendANAFRequest(string $Method, ?string $body = null, array|null $queryParams=null,  bool $hasAuth=false, string $contentType="application/json"): ResponseInterface
    {
        $options = ["headers" =>
            [
                "Content-Type" => $contentType,
                "Accept" => "*/*",
                "Cache-Control" => "no-cache",

            ]
        ];
        if($hasAuth)
        {
            if(!$this->HasToken())
            {
                throw new Error("ANAF API Error: No token");
            }
            $options["headers"]["Authorization"]="Bearer ".$this->AccessToken->getToken();
        }
        $options["query"]=$queryParams;
        $options["timeout"]=$this->Timeout;
        if ($body === null)
        {
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
        try
        {
            if ($sanitizedCUI === false || !self::ValidateCIF($sanitizedCUI))
            {
                $response->success = false;
                $response->message = "CUI Invalid: $cui";
                return $response;
            }
            $requestBody = [["cui" => $sanitizedCUI, "data" => date("Y-m-d")]];
            $httpResponse = $this->SendANAFRequest("PlatitorTvaRest/api/v8/ws/tva", json_encode($requestBody));
            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300)
            {
                $response->success = true;
                $content = $httpResponse->getBody()->getContents();
                //var_dump($content);
                $response->rawResspone = $content;
                if (!$response->Parse())
                {
                    $response->success = false;
                    $response->message = "Eroare interpretare raspuns ANAF: " . $response->LastParseError. "\n" . $response->rawResspone;
                }
                return $response;
            }
            $response->success = false;
            $response->message = "HTTP Error: " . $httpResponse->getStatusCode();
        }
        catch (Throwable $ex)
        {
            $response->success = false;
            $response->message = "Eroare ANAF: " . $ex->getMessage();
            error_log("ANAF API Error:\n" . $ex->getMessage()."\n".$ex->getTraceAsString());
        }
        return $response;
    }

    /**
     * @param string $ubl UBL XML content to upload
     * @param bool $extern If true, the invoice is marked as "extern" (not issued to a romanian company)
     * @param bool $autoFactura If true, the invoice is marked as "autofactura" (issued by the buyer in the sellers name)
     * @return UBLUploadResponse
     */
    public function UploadEFactura(string $ubl, bool $extern=false, bool $autoFactura=false):UBLUploadResponse
    {
        $response = new UBLUploadResponse();
        try
        {
            $modeName = $this->Production ? "prod" : "test";
            $method = "https://api.anaf.ro/$modeName/FCTEL/rest/upload";
            $queryParams = ["standard" => "UBL", "cif" => "22354360"];
            if($extern)
            {
                $queryParams["extern"]="DA";
            }
            if($autoFactura)
            {
                $queryParams["autofactura"]="DA";
            }
            $httpResponse = $this->SendANAFRequest($method, $ubl, $queryParams, true);
            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300)
            {
                //var_dump($httpResponse);
                $content = $httpResponse->getBody()->getContents();
                error_log($content);
                $response->rawResspone = $content;
                if (!$response->Parse())
                {
                    $response->success = false;
                    $response->message = "Eroare interpretare raspuns ANAF: " . $response->LastParseError;
                }
            }
        }
        catch (Throwable $ex)
        {
            $response->success = false;
            $response->message = "Eroare ANAF: " . $ex->getMessage();
            error_log("ANAF API Error:\n" . $ex->getMessage()."\n".$ex->getTraceAsString());
        }
        return $response;
    }

    /**
     * Download Answer from ANAF
     * To obtain the ID, use @see ANAFAPIClient::ListAnswers()
     * @param string $id
     * @return string either an error message (starts with "ERROR_") or zip file content
     */
    public function DownloadAnswer(string $id):string
    {
        $modeName = $this->Production ? "prod" : "test";
        $method = "https://api.anaf.ro/$modeName/FCTEL/rest/descarcare?id=$id";
        try
        {
            $httpResponse = $this->SendANAFRequest($method, null, null, true);
            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300)
            {
                //var_dump($httpResponse);
                $content = $httpResponse->getBody()->getContents();
                if(str_starts_with($content, "PK"))
                {
                    return $content;
                }
                return "ERROR_BAD_CONTENT";

            }
        }
        catch (Throwable $ex)
        {
            error_log("ANAF API Error:\n" . $ex->getMessage()."\n".$ex->getTraceAsString());
            return "ERROR";
        }
        return "ERROR_NO_RESPONSE";
    }

    /**
     * ANAF API method to convert xml (UBL) to PDF
     * @param string $ubl UBL XML content
     * @param string $metadata Metadata for the request, used for error logging only
     * @return string|false false for errors, PDF content otherwise
     */
    public function UBL2PDF(string $ubl, string $metadata):string|false
    {
        if (strpos($ubl, 'xsi:schemaLocation') !== false)
        {
            $ubl=$this->RemoveSchemaLocationAttribute($ubl);
        }
        $modeName = $this->Production ? "prod" : "test";
        $method = "https://webservicesp.anaf.ro/$modeName/FCTEL/rest/transformare/FACT1/DA";
        try
        {
            $httpResponse = $this->SendANAFRequest($method, $ubl, null, false, "text/plain");
            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300)
            {
                //var_dump($httpResponse);
                $content = $httpResponse->getBody()->getContents();
                if(str_starts_with($content, "%PDF"))
                {
                    return $content;
                }
                error_log("Bad content format, expected PDF (UBL2PDF) - $metadata");
                error_log($content);
                return $content;

            }
        }
        catch (Throwable $ex)
        {
            error_log("ANAF API Error:\n" . $ex->getMessage()."\n".$ex->getTraceAsString());
            return false;
        }
        return false;
    }

    /**
     * Removes the xsi:schemaLocation attribute from the root element of the UBL XML
     * @param $xmlString
     * @return string
     */
    private function RemoveSchemaLocationAttribute($xmlString):string
    {
        $dom = new DOMDocument();
        $dom->loadXML($xmlString, LIBXML_NOERROR | LIBXML_NOWARNING); // Load XML with error handling
        $rootElement = $dom->documentElement;
        // Check if the attribute exists and remove it
        if ($rootElement->hasAttribute('xsi:schemaLocation'))
        {
            $rootElement->removeAttribute('xsi:schemaLocation');
            $xmlString = $dom->saveXML();
        }

        return $xmlString;
    }

    /**
     * Get answer list for a company (authenticated user must have access to the company!)
     * @param int $cif
     * @param int $days Number of days to look back
     * @return ANAFAnswerListResponse
     */
    public function ListAnswers(int $cif, int $days=60):ANAFAnswerListResponse
    {
        $modeName = $this->Production ? "prod" : "test";
        $method = "https://api.anaf.ro/$modeName/FCTEL/rest/listaMesajeFactura?zile=$days&cif=$cif";
        try
        {
            $httpResponse = $this->SendANAFRequest($method, null, null, true);
            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300)
            {
                //var_dump($httpResponse);
                $content = $httpResponse->getBody()->getContents();
                return ANAFAnswerListResponse::CreateFromParsed(json_decode($content));
            }
        }
        catch (Throwable $ex)
        {
            error_log("ANAF API Error:\n" . $ex->getMessage()."\n".$ex->getTraceAsString());
            return ANAFAnswerListResponse::CreateError();
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
    public function VerifyXML(string $ubl):bool
    {
        try
        {
            $method = "https://webservicesp.anaf.ro/prod/FCTEL/rest/validare/FACT1";
            $httpResponse = $this->SendANAFRequest($method, $ubl, null,false,"text/plain");
            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300)
            {
                //var_dump($httpResponse);
                $contentString=$httpResponse->getBody()->getContents();
                var_dump($contentString);;
                $content = ANAFVerifyResponse::CreateFromParsed(json_decode($contentString));
                return $content->IsOK();
            }
        }
        catch (Throwable $ex)
        {
            error_log("ANAF API Error:\n" . $ex->getMessage()."\n".$ex->getTraceAsString());
            return false;
        }
        error_log("ANAF VERIFY ERROR: NO RESPONSE OR ERROR");
        return false;
    }

    /**
     * Calls @see ANAFAPIClient::$ErrorCallback with given message
     * @param string $message
     * @return void
     */
    private function CallErrorCallback(string $message):void
    {
        if($this->ErrorCallback!=null)
        {
            ($this->ErrorCallback)($message);
        }
    }

    /**
     * Check if the client has a valid access token.
     * Tries to load it using @see ANAFAPIClient::LoadAccessToken() and refreshes it if it has expired.
     * @return bool
     */
    public function HasToken():bool
    {
        if($this->AccessToken==null)
        {
            $token = $this->LoadAccessToken();
            if($token==null)
            {
                error_log("ANAF API Token null after load(1)!");
                return false;
            }
            $this->AccessToken=$token;
        }
        else
        {
            $token = $this->AccessToken;
        }
        try
        {
            if(!isset($token))
            {
                error_log("ANAF API Token null after load!(2)");
                return false;
            }
            if ($token->hasExpired())
            {
                if(!$this->RefreshToken())
                {
                    error_log("ANAF API Token refresh failed!");
                    return false;
                }
            }
        }
        catch (Error $ex)
        {
            error_log("ANAF API Token refresh failed: ".$ex->getMessage()."\n".$ex->getTraceAsString());
            return false;
        }
        return true;

    }

    /**
     * @see ANAFAPIClient::$Production
     * @return bool
     */
    public function IsProduction():bool
    {
        return $this->Production;
    }

    /**
     * Verifies a CUI given the official checksum algorithm
     * @param string|false $cif
     * @return bool
     */
    public static function ValidateCIF(string|false $cif):bool
    {
        if($cif===false)
        {
            return false;
        }
        if (!is_int($cif))
        {
            $cif = strtoupper($cif);
            if (strpos($cif, 'RO') === 0)
            {
                $cif = substr($cif, 2);
            }
            $cif = (int)trim($cif);
        }

        if (strlen($cif) > 10 || strlen($cif) < 2)
        {
            return false;
        }
        $v = 753217532;
        $c1 = $cif % 10;
        $cif = (int)($cif / 10);
        $t = 0;
        while ($cif > 0)
        {
            $t += ($cif % 10) * ($v % 10);
            $cif = (int)($cif / 10);
            $v = (int)($v / 10);
        }
        $c2 = $t * 10 % 11;
        if ($c2 == 10)
        {
            $c2 = 0;
        }
        return $c1 === $c2;
    }
}