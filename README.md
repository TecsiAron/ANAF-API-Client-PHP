# ANAF-API-Client-PHP
Citeste CHANGELOG.md inainte de a face update de la v1.1.1-alpha la v2.0.0-beta  
API ANAF pentru interogare CIF si upload RO-eFactura.  
Foloseste PHP 8+  
# Versiunea minima de PHP se va schimba din 8.0 in 8.1 in viitorul apropriat!
Pentru a instala:  
```
composer require tecsiaron/anaf-api-client-php  
```
Exemplu:  
```phg
<?php  
use EdituraEDU\ANAF\ANAFAPIClient;  
$Logger= function (string $message, ?Throwable $ex = null)  
{  
	echo $message;
	if($ex !== null)
	{
		echo $ex->getMessage();
	}
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
# Functii:
- Accesarea registrului de inregistrari in scopuri de TVA  
- Autentificare OAuth  
- Incarcarea unei facturi UBL in sistemul RO e-Factura  
- Validare UBL prin API ANAF (API instabil)  
- Conversie UBL in PDF prin API ANAF  
- Listare raspunsuri din SPV  
- Descarcare raspuns din SPV   
