<?php

namespace App\Domain\Invoices\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case PRINTED = 'printed';
    case CANCELLED = 'cancelled';
    case REVERSED = 'reversed';
}
