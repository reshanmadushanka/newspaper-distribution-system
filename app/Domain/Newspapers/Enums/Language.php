<?php

namespace App\Domain\Newspapers\Enums;

enum Language: string
{
    case ENGLISH = 'English';
    case TAMIL = 'Tamil';
    case SINHALA = 'Sinhala';

    public static function options(): array
    {
        return [
            self::ENGLISH->value => 'English',
            self::TAMIL->value => 'Tamil',
            self::SINHALA->value => 'Sinhala',
        ];
    }
}
