<?php

namespace App\Domain\Forecasting\Enums;

enum ForecastMethod: string
{
    case SAME_WEEKDAY = 'same_weekday';
    case MOVING_AVERAGE = 'moving_average';
    case MANUAL = 'manual';
}
