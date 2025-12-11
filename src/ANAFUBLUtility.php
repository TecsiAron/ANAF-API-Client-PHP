<?php

namespace EdituraEDU\ANAF;

use EdituraEDU\ANAF\Callback\ANAFAPICallbackManager;

class ANAFUBLUtility
{
    private bool $_IsUsable;
    private static ANAFUBLUtility|null $_Instance=null;
    private const REQUIRED_CLASSES=["\ZipArchive", "\DOMDocument"];

    public static function GetInstance(): ANAFUBLUtility
    {
        if(self::$_Instance===null)
            self::$_Instance=new ANAFUBLUtility();
        return self::$_Instance;
    }

    private function __construct()
    {
        $isUsable=true;
        foreach(self::REQUIRED_CLASSES as $className)
        {
            if(!class_exists($className))
            {
                $isUsable=false;
                break;
            }
        }
        $this->_IsUsable=$isUsable;
    }

    public function IsUsable(): bool
    {
        return $this->_IsUsable;
    }

    public function ExtractANAFAnswer(string $zipContent, string $answerID): array|false
    {
        if(!$this->_IsUsable)
        {
            ANAFAPICallbackManager::GetInstance()->WriteErrorLog("ext-zip  or ext-dom not installed, cannot parse signed invoice ($answerID)");
            return false;
        }
        // Create a temporary file
        $tempFileName = tempnam(sys_get_temp_dir(), 'zip');

        // Write the ZIP string to the temporary file
        file_put_contents($tempFileName, $zipContent);

        // Note: ZipArchive is already checked in constructor
        /** @noinspection PhpComposerExtensionStubsInspection */
        $zip = new \ZipArchive();
        $signature = "";
        $content = "";
        // Open the stream with ZipArchive
        if ($zip->open($tempFileName))
        {
            if ($zip->numFiles != 2)
            {
                unlink($tempFileName);
                $zip->close();
                ANAFAPICallbackManager::GetInstance()->WriteErrorLog("Unexpected file count in zip ($answerID)");
                return false;
            }
            for ($i = 0; $i < $zip->numFiles; $i++)
            {
                $FileContent = $zip->getFromIndex($i);
                $FileName = $zip->getNameIndex($i);
                if (str_starts_with(strtolower($FileName), "semnatura_"))
                {
                    $signature = $FileContent;
                }
                else if ($FileName == "$answerID.xml")
                {
                    $content = $FileContent;
                }
                else
                {
                    ANAFAPICallbackManager::GetInstance()->WriteErrorLog("Unexpected file(" . $FileName . ") in zip ($answerID). First 32 bytes:" . substr($FileContent, 0, 32));
                    unlink($tempFileName);
                    $zip->close();

                    return false;
                }
            }
            $zip->close();
        }
        else
        {
            unlink($tempFileName);
            ANAFAPICallbackManager::GetInstance()->WriteErrorLog("Failed to open zip ($answerID)");
            return false;
        }


        // Clean up: Delete the temporary file
        unlink($tempFileName);
        if (empty($signature) || empty($content))
        {
            ANAFAPICallbackManager::GetInstance()->WriteErrorLog("Empty signature or content ($answerID)");
            return false;
        }
        return [$signature, $content];
    }

    /**
     * @param string $ublContent
     * @param string $answerID
     * @param bool $strictMode If true, throws an exception on failure
     * @return array|string
     * @throws \Exception If strict mode is enabled and an error occurs
     */
    public function GetCIFsFromUBL(string $ublContent, string $answerID, bool $strictMode=false): array|string
    {
        if(!$this->_IsUsable)
        {
            $error="ext-zip  or ext-dom not installed, cannot parse signed invoice ($answerID)";
            if($strictMode)
            {
                throw new \Exception($error);
            }
            return $error;
        }
        /** @noinspection PhpComposerExtensionStubsInspection */
        $doc = new \DOMDocument();
        try {
            $doc->loadXML($ublContent);
        }
        catch (\Throwable $e)
        {
            if($strictMode)
                throw $e;
            return "Failed to parse UBL XML: ".$e->getMessage();
        }
        $sellerCIF=$this->ExtractCIF($doc, "AccountingSupplierParty");
        $buyerCIF=$this->ExtractCIF($doc, "AccountingCustomerParty");
        if($sellerCIF===false || $buyerCIF===false)
        {
            $error="Failed to exact one or both CIFs from UBL for answer $answerID";
            if($strictMode)
                throw new \Exception($error);
            return $error;
        }
        return [$sellerCIF, $buyerCIF];
    }

    /**
     * @param \DOMDocument $document
     * @param string $partySchemaName
     * @return string|false
     * @noinspection PhpComposerExtensionStubsInspection
     */
    private function ExtractCIF(mixed $document, string $partySchemaName): string|false
    {


        /** @noinspection PhpComposerExtensionStubsInspection */
        $xpath = new \DOMXPath($document);
        $result = $xpath->query("/cac:Invoice/cac:$partySchemaName/cac:Party/cac:PartyTaxScheme/cbc:CompanyID", null);
        if(count($result)!=0)
        {
            ANAFAPICallbackManager::GetInstance()->WriteErrorLog("Expected 1 $partySchemaName CIF, found ".count($result));
            return false;
        }
        return $result->item(0)->textContent;
    }
}