<?php

namespace App\Domain\Newspapers\Enums;

enum NewspaperStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DISCONTINUED = 'discontinued';

    public static function options(): array
    {
        return [
            self::ACTIVE->value => 'Active',
            self::INACTIVE->value => 'Inactive',
            self::DISCONTINUED->value => 'Discontinued',
        ];
    }
}
