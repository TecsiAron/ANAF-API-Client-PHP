<?php

namespace EdituraEDU\ANAF\Responses;

use Throwable;

class ExtractedAnswer extends ANAFResponse
{

    public string|null $content = null;
    public string|null $signature = null;
    private string $TempFileName;

    private function DeleteTempFile(): void
    {
        try {
            @unlink($this->TempFileName);
        } catch (\Throwable $ex) {
            //swallow exception
        }
    }

    public static function IsSupported(): bool
    {
        return class_exists('\ZipArchive');
    }

    public function Parse(): void
    {
        if (!self::IsSupported()) {
            $this->LastError = new ANAFException("ZipArchive not supported", ANAFException::ZIP_NOT_SUPPORTED);
            return;
        }
        if (!str_starts_with($this->rawResponse, "PK")) {
            $this->LastError = new ANAFException("Invalid zip file", ANAFException::UNEXPECTED_ZIP_FORMAT);
            return;
        }
        $zip = new \ZipArchive();
        $tmpDir = sys_get_temp_dir();
        $this->TempFileName = tempnam($tmpDir, 'ANAF_');
        $prevException = null;
        if ($this->TempFileName === false) {
            $this->LastError = new ANAFException("Failed to create temporary file", ANAFException::FAILED_TO_WRITE_TEMP_FILE);
            return;
        }
        $written = false;
        try {
            if (file_put_contents($this->TempFileName, $this->rawResponse) !== false) {
                $written = true;
            }
        } catch (Throwable $ex) {
            $prevException = $ex;
        }
        if (!$written) {
            $this->LastError = new ANAFException("Failed to write temporary file", ANAFException::FAILED_TO_WRITE_TEMP_FILE, $prevException);
            return;
        }
        if ($zip->open($this->TempFileName)) {
            if ($zip->numFiles != 2) {
                $this->DeleteTempFile();
                $zip->close();
                $this->LastError = new ANAFException("Unexpected number of files in zip: " . $zip->numFiles, ANAFException::UNEXPECTED_ZIP_FORMAT);
                return;
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $FileContent = $zip->getFromIndex($i);
                $FileName = $zip->getNameIndex($i);
                if (str_starts_with(strtolower($FileName), "semnatura_")) {
                    $signature = $FileContent;
                    $signatureFileName = $FileName;
                } else {
                    $content = $FileContent;
                    $contentFileName = $FileName;
                }
            }
            $zip->close();
            if (empty($signatureFileName) || empty($contentFileName)) {
                $this->LastError = new ANAFException("Missing required files in zip: signature or content", ANAFException::UNEXPECTED_ZIP_FORMAT);
                return;
            }
            $explodedFileName = explode("_", $signatureFileName);
            if (count($explodedFileName) != 2) {
                $this->LastError = new ANAFException("Invalid signature file name: " . $signatureFileName, ANAFException::UNEXPECTED_ZIP_FORMAT);
                return;
            }
            $expectedID = explode(".", $explodedFileName[1])[0];
            if (empty($expectedID)) {
                $this->LastError = new ANAFException("Failed to detect index incarcare from signature file name: " . $signatureFileName, ANAFException::UNEXPECTED_ZIP_FORMAT);
                return;
            }
            if (strtolower($contentFileName) != "$expectedID.xml") {
                $this->LastError = new ANAFException("Unexpected content file name: " . $contentFileName, ANAFException::UNEXPECTED_ZIP_FORMAT);
                return;
            }
            $this->content = $content;
            $this->signature = $signature;
        } else {
            $this->DeleteTempFile();
            $this->LastError = new ANAFException("Failed to open zip file");
        }
    }

    public static function Create($rawResponse): self
    {
        $response = new ExtractedAnswer();
        $response->rawResponse = $rawResponse;
        $response->Parse();
        return $response;
    }

    public static function CreateError(Throwable $error): self
    {
        $result = new ExtractedAnswer();
        $result->LastError = $error;
        return $result;
    }
}