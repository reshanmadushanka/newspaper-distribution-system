<?php

namespace App\Domain\Admin\Enums;

enum SystemInvoiceStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PENDING => 'bg-yellow-100 text-yellow-800',
            self::PAID => 'bg-green-100 text-green-800',
        };
    }
}
