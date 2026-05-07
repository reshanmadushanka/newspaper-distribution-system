<?php

namespace App\Domain\Newspapers\Enums;

enum PublicationFrequency: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case SUNDAY = 'sunday';
    case CUSTOM = 'custom';

    public static function options(): array
    {
        return [
            self::DAILY->value => 'Daily',
            self::WEEKLY->value => 'Weekly',
            self::SUNDAY->value => 'Sunday Only',
            self::CUSTOM->value => 'Custom Schedule',
        ];
    }
}
