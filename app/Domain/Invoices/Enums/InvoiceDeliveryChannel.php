<?php

namespace App\Domain\Invoices\Enums;

enum InvoiceDeliveryChannel: string
{
    case PRINT = 'print';
    case EMAIL = 'email';
    case WHATSAPP = 'whatsapp';
}
