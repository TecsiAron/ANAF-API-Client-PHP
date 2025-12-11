<?php

namespace EdituraEDU\ANAF\Callback;

interface IInvoiceDownloadedCallback
{
    function CaresAbout(int $cif):bool;
    function OnInvoiceDownloaded(string $answerID, string $ublContent):void;
}