<?php

namespace EdituraEDU\ANAF\Callback;

use EdituraEDU\ANAF\Responses\ANAFAnswer;

interface IANAFAnswerCallback
{
    function CaresAbout(int $cif):bool;
    function OnAnswerReceived(ANAFAnswer $answer):void;
}