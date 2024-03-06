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
