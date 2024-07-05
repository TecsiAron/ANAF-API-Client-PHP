# v2.0.1-beta  
ANAFAPIClient->AccessToken is now protected (was private)  
# v2.0.0-beta
In order to update/cleanup the code I've jumped to a new major version. Please read the breaking changes section below before you update.
# BREAKING CHANGE
Mostly internal changes:
- ANAFAPIResponse renamed to ANAFResponse  
- Added ANAFResponse::LastError to store the last error message, use LastError->getMessage() and LastError->getCode() to get more info  
- ANAFResponse::success removed, use ANAFResponse::IsSuccess() or ANAFResponse::HasError() instead  
- ANAFResponse::message removed, use ANAFResponse::LastError to get info  
- ANAFResponse::LastParseError removed  
- ANAFResponse::Parse return type changed from bool to void  
- ANAFAnswerListResponse now extends ANAFResponse and success and eroare properties removed  
- Removed CreateFromParsed from all implementers of ANAFResponse  
- ANAFAPIClient::VerifyXML no longer returns bool, returns ANAFVerifyResponse instead  
- UBLUploadResponse no longer stores index_incarcare in message, has its own field UBLLoadResponse::IndexIncarcare  
- ANAFAPIClient::DoEntityFetch now only takes 2 arguments (third was unused)  

Non breaking changes:  
- ANAFAPIClient no longer extends GuzzleHttp\Client  
- ANAFAPIClient no longer uses fully formed URLs for API calls  
- Added ANAFAPIClient::LockToken to facilitate "locking" the token file by the test suite (Token will not be refreshed or saved during testing)  
- ANAFAPIClient::RemoveSchemaLocationAttribute is public static now  
- ANAFAPIClient::SendANAFRequest and ANAFAPIClient::UBL2PDF now both have a new optional parameter to override the default timeout. (5s is sometime not enough)  
- ANAFAPIClient::VerifyXML and ANAFAPIClient::UBL2PDF now have a new optional boolean parameter called $authenticated. If set to true the OAuth2 API endpoint will be used instead of the public endpoint. By default this is true!  
- ANAFResponse defines two new abstract function Create and CreateError  
- Added ANAFException class, mostly to hold error code constants.  
- Removed (unused) dependency/ using statement for DOMDocument  
- Added new dependency ext-simplexml  
- Added PHUnit as a dev dependency  
- Wrote tests for all response parsing  
- Wrote tests for some API calls
- Fixed an issue where UBl2PDF failed if the client was in testing mode (the API has no backend for testing)  
- Added run-tests-example.bat  


# v1.1.1-alpha  
- RemoveSchemaLocationAttribute no longer uses DOMDocument to strip xsi:schemaLocation attribute. Uses regex instead! (DOMDocument caused side effects with special characters that could cause UBL2PDF to fail).
- Removed, now unnecessary, dependencies (ext-dom and ex-libxml)
  
# v1.1.0-alpha  
- LoadAccessToken() no longer causes unhandled exception when file does not exist.  
- Location of the token file is now configurable. (Check $TokenFilePath and ANAFAPIClient::__construct())  
- Moved token load/refresh logic from HasAccessToken() to LoadAccessToken() and RefreshAccessToken().  
- Added optional parameter to LoadAccessToken() to enable/disable automatic token refresh.  
- HasAccessToken() now calls LoadAccessToken() with automatic refresh enabled. (logic moved)  
- The error callback method now has a second, optional, parameter: ?Throwable $ex = null.  
- Removed some unnecessary error_log calls.  
- General code cleanup.

BREAKING CHANGES:  
=================  
- HasToken() renamed to HasAccessToken(). (for consistency)  
- GetAccessToken() no longer has a parameter and serves as a getter for $AccessToken instead of loading the token from ANAF. Functionality as it was moved to a new method (ProcessOAuthCallback).  
- RefreshToken() renamed to RefreshAccessToken(). (for consistency)  
- SaveAccessToken() is now private (called automatically by ProcessOAuthCallback and RefreshAccessToken).  
- UploadEFactura() added a new (required) parameter: $sellerCIF (previously the CIF was hardcoded)  


# v1.0.4-alpha  
Added timeout to all outgoing requests (5s by default)  
  
# v1.0.3-alpha  
Add extern and autofactura params for UploadEFactura  

# v1.0.2-alpha  
Creating composer package  
Moved classes to new namespace (EdituraEDU\ANAF)  
Clarified extension dependencies (ext-dom and ext-libxml)  

# v1.0.1  
New dependency: php-xml(extension)  
Cleaned up some error_log calls.  
Added ListAnswers API call.  
Added DownloadAnswer API call.  
Added UBL2PDF API call.  
Added VerifyXML API call. IMPORANT: At the time of writing the API responses are unpredicatable theirfor this method should not be used!  
Fixed some typos.  
  
# v1.0.0  
Initial release  
