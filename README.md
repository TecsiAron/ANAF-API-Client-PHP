# ANAF-API-Client-PHP
API ANAF pentru interogare CIF si upload RO-eFactura.  
Foloseste PHP 8+  

Pentru a instala:  
```
composer require tecsiaron/anaf-api-client-php  
```
Exemplu:  
```phg
<?php  
use EdituraEDU\ANAF\ANAFAPIClient;  
$Logger= function (string $message)  
{  
	echo $message;  
};  
$anaf = new ANAFAPIClient(ANAF_OAUTH,false, $Logger);  
var_dump($anaf->GetEntity("RO12345678"));  
```
Formatul pentru datele de oauth:

```
const ANAF_OAUTH=[
    'clientId' => 'client_id_din_contul_de_dezvoltator',
    'clientSecret' => 'client_secret',
    'redirectUri' => 'redirect_url',
    'urlAuthorize' => 'https://logincert.anaf.ro/anaf-oauth2/v1/authorize',
    'urlAccessToken' => 'https://logincert.anaf.ro/anaf-oauth2/v1/token',
    'urlResourceOwnerDetails' => 'https://logincert.anaf.ro/anaf-oauth2/v1/resource'
];
```
Documentatie: https://tecsiaron.github.io/ANAF-API-Client-PHP/
