<?php

namespace EdituraEDU\Admin\ANAF;

use EdituraEDU\Admin\ANAF\Responses\EntityResponse;
use EdituraEDU\Admin\ANAF\Responses\TVAResponse;
use EdituraEDU\Admin\ANAF\Responses\UBLUploadResponse;
use Error;
use GuzzleHttp\Client;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use Throwable;

require_once dirname(__FILE__) . "/../lib/vendor/autoload.php";

class ANAFAPIClient extends Client
{
    private bool $Production;
    private ?AccessToken $AccessToken=null;
    /** @var callable|null $ErrorCallback */
    private $ErrorCallback=null;
    private array $OAuthConfig;
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

    public function GetEntity(string $cui):EntityResponse
    {
        $cui=trim($cui);
        $sanitizedCUI = $this->SanitizeCUI($cui);
        $response = new EntityResponse();

        return $this->DoEntityFetch($sanitizedCUI, $response, $cui);
    }
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

    public function GetLoginURL(): string
    {
        $provider = $this->GetOAuthProvider();
        return $provider->getAuthorizationUrl(['token_content_type' => 'jwt']);
    }

    public function GetAccessToken(string $authCode): AccessToken
    {
        $provider = $this->GetOAuthProvider();
        return $provider->getAccessToken('authorization_code', ["code" => $authCode, 'token_content_type' => 'jwt']);
    }

    public function SaveAccessToken(AccessToken $token)
    {
        $tokenJson = json_encode($token);
        file_put_contents(dirname(__FILE__) . "/ANAFAccessToken.json", $tokenJson);
    }

    public function LoadAccessToken(): ?AccessToken
    {
        try
        {
            $tokenJson = file_get_contents(dirname(__FILE__) . "/ANAFAccessToken.json");
            $token = json_decode($tokenJson, true);
            return new AccessToken($token);
        } catch (Throwable $ex)
        {
            return null;
        }
    }

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

    private function GetOAuthProvider(): GenericProvider
    {
        return new GenericProvider($this->OAuthConfig);
    }

    private function SendANAFRequest(string $Method, ?string $body = null, array|null $queryParams=null,  bool $hasAuth=false): ResponseInterface
    {
        $options = ["headers" =>
            [
                "Content-Type" => "application/json",
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
        if ($body === null)
        {
            return $this->get($Method, $options);
        }
        $options['body'] = $body;
        return $this->post($Method, $options);
    }

    /**
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
                    $response->message = "Eroare interpretare raspuns ANAF: " . $response->LastParseError;
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

    public function UploadEFactura(string $ubl):UBLUploadResponse
    {
        $response = new UBLUploadResponse();
        try
        {
            $modeName = $this->Production ? "prod" : "test";
            $method = "https://api.anaf.ro/$modeName/FCTEL/rest/upload";
            $queryParams = ["standard" => "UBL", "cif" => "22354360"];
            $httpResponse = $this->SendANAFRequest($method, $ubl, $queryParams, true);
            if ($httpResponse->getStatusCode() >= 200 && $httpResponse->getStatusCode() < 300)
            {
                //var_dump($httpResponse);
                $content = $httpResponse->getBody()->getContents();
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

    private function CallErrorCallback(string $message)
    {
        if($this->ErrorCallback!=null)
        {
            ($this->ErrorCallback)($message);
        }
    }

    public function HasToken():bool
    {
        if($this->AccessToken==null)
        {
            $token = $this->LoadAccessToken();
            if($token==null)
            {
                return false;
            }
            $this->AccessToken=$token;
        }
        try
        {
            if ($token->hasExpired())
            {
                if(!$this->RefreshToken())
                {
                    return false;
                }
            }
        }
        catch (Error $ex)
        {
            return false;
        }
        return true;

    }

    public function IsProduction():bool
    {
        return $this->Production;
    }

    public static function ValidateCIF(string|false $cif)
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