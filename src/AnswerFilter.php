<?php

namespace EdituraEDU\ANAF;

enum AnswerFilter: string {
    case ERORI_FACTURA = 'E';
    case FACTURA_TRIMISA = 'T';
    case FACTURA_PRIMITA = 'P';
    case ALTE_MESAJE = 'R';
}
