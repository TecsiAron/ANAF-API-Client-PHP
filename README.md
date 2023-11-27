# ANAF-API-Client-PHP
API ANAF pentru interogare CIF si upload RO-eFactura
Necesita guzzlehttp/guzzle si league/oauth2-client.
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
Va recomand un auto loader custom, vezi [aici](https://www.php.net/manual/en/language.oop5.autoload.php).