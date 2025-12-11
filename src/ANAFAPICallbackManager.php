<?php

namespace EdituraEDU\ANAF;

class ANAFAPICallbackManager
{

    private static ANAFAPICallbackManager|null $_Instance=null;
    public static function GetInstance(): ANAFAPICallbackManager
    {
        if(self::$_Instance===null)
            self::$_Instance = new ANAFAPICallbackManager();
        return self::$_Instance;
    }

    private array $ErrorLogCallbacks = [];
    private array $InvoiceDownloadedCallbacks = [];

    private array $AnswerMonitorCallbacks = [];

    public function RegisterErrorLogCallback(callable $callback)
    {
        $this->ErrorLogCallbacks[] = $callback;
    }

    public function RegisterInvoiceDownloadedCallback(int $cif, callable $callback)
    {
        $this->InvoiceDownloadedCallbacks[$cif][] = $callback;
    }

    public function RegisterAnswerMonitorCallback(int $cif, callable $callback)
    {
        $this->AnswerMonitorCallbacks[] = $callback;
    }

    public function WriteErrorLog(string $message)
    {
        if ($this->ErrorLogCallbacks != null && count($this->ErrorLogCallbacks) > 0) {
            foreach ($this->ErrorLogCallbacks as $callback) {
                $callback($message);
            }
        } else
            error_log($message);
    }

    public function CanCallInvoiceDownloadedCallbacks()
    {
        if(count($this->InvoiceDownloadedCallbacks) == 0)
            return false;
        if(class_exists("\ZipArchive", false))
        {
            return true;
        }
        $this->WriteErrorLog("You've registered invoice downloaded callbacks, but ext-zip is not installed. Your callbacks will be ignored.");
        return false;
    }

    public function WriteInvoiceDownloaded(string $ublContent)
    {

    }
}