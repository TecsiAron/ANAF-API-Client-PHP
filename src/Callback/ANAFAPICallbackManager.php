<?php

namespace EdituraEDU\ANAF\Callback;

use EdituraEDU\ANAF\Responses\ANAFAnswerListResponse;
use EdituraEDU\ANAF\ANAFUBLUtility;

class ANAFAPICallbackManager
{

    private static ANAFAPICallbackManager|null $_Instance = null;

    public static function GetInstance(): ANAFAPICallbackManager
    {
        if (self::$_Instance === null)
            self::$_Instance = new ANAFAPICallbackManager();
        return self::$_Instance;
    }

    /**
     * @var ILogCallback[] $ErrorLogCallbacks
     */
    private array $ErrorLogCallbacks = [];
    /**
     * @var IInvoiceDownloadedCallback[] $InvoiceDownloadedCallbacks
     */
    private array $InvoiceDownloadedCallbacks = [];

    /**
     * @var IANAFAnswerCallback[] $ANAFAnswerCallbacks
     */
    private array $ANAFAnswerCallbacks = [];

    public function RegisterErrorLogCallback(ILogCallback $callback)
    {
        $this->ErrorLogCallbacks[] = $callback;
    }

    public function RegisterInvoiceDownloadedCallback(IInvoiceDownloadedCallback $callback): void
    {
        $this->InvoiceDownloadedCallbacks[] = $callback;
    }

    public function RegisterAnswerRecievedCallback(IANAFAnswerCallback $callback)
    {
        $this->ANAFAnswerCallbacks[] = $callback;
    }

    public function WriteErrorLog(string $message, ?\Throwable $ex = null)
    {
        if ($this->ErrorLogCallbacks != null && count($this->ErrorLogCallbacks) > 0) {
            foreach ($this->ErrorLogCallbacks as $callback) {
                $callback->Log($message, $ex);
            }
        } else
        {
            error_log($message);

            if ($ex != null) {
                error_log($ex->getMessage());
                error_log($ex->getTraceAsString());
            }
        }

    }

    public function CallAnswerReceived(ANAFAnswerListResponse $answerList)
    {
        if ($answerList->IsSuccess()) {
            for ($i = 0; $i < count($answerList->mesaje); $i++) {
                $answer = $answerList->mesaje[$i];
                for ($j = 0; $j < count($this->ANAFAnswerCallbacks); $j++) {
                    if ($this->ANAFAnswerCallbacks[$j]->CaresAbout($answer->cif))
                        $this->ANAFAnswerCallbacks[$j]->OnAnswerReceived($answer);
                }
            }
        }
    }

    public function CallInvoiceDownloaded(string $rawContent, string $answerID): void
    {
        if (count($this->InvoiceDownloadedCallbacks) == 0)
            return;
        if (!ANAFUBLUtility::GetInstance()->IsUsable()) {
            $this->WriteErrorLog("ext-zip  or ext-dom not installed, cannot parse signed invoice");
            return;
        }
        $ublContent = null;
        if (str_starts_with($rawContent, "PK")) {
            $extracted = ANAFUBLUtility::GetInstance()->ExtractANAFAnswer($rawContent, $answerID)[0];
            if ($extracted === false) {
                $this->WriteErrorLog("Failed to extract signed invoice");
                return;
            }
            $ublContent = $extracted[0];
        } else if (str_starts_with(trim($rawContent), "<Invoice")) {
            $ublContent = $rawContent;
        } else {
            $this->WriteErrorLog("Unexpected answer format for answer $answerID");
            return;
        }
        $cifs = ANAFUBLUtility::GetInstance()->GetCIFsFromUBL($ublContent, $answerID);
        if (is_string($cifs)) {
            $this->WriteErrorLog($cifs);
            return;
        }
        foreach ($cifs as $cif) {
            $this->CallInvoiceDownloadedInternal($cif, $answerID, $ublContent);
        }
    }

    private function CallInvoiceDownloadedInternal(string $cif, string $answerID, string $ublContent)
    {
        if (count($this->InvoiceDownloadedCallbacks) == 0)
            return;
        for ($i = 0; $i < count($this->InvoiceDownloadedCallbacks); $i++) {
            if ($this->InvoiceDownloadedCallbacks[$i]->CaresAbout($cif)) {
                $this->InvoiceDownloadedCallbacks[$i]->OnInvoiceDownloaded($answerID, $ublContent);
            }

        }
    }
}