<?php

namespace App\Domain\Shops\Enums;

enum InvoiceDeliveryMethod: string
{
    case PRINT = 'print';
    case EMAIL = 'email';
    case WHATSAPP = 'whatsapp';

    public function label(): string
    {
        return match($this) {
            self::PRINT => 'Print Only',
            self::EMAIL => 'Email',
            self::WHATSAPP => 'WhatsApp',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
