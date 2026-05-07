<?php

namespace Database\Factories;

use App\Domain\Forecasting\Models\DispatchForecast;
use App\Domain\Forecasting\Enums\ForecastMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class DispatchForecastFactory extends Factory
{
    protected $model = DispatchForecast::class;

    public function definition(): array
    {
        return [
            'shop_id' => \App\Domain\Shops\Models\Shop::factory(),
            'newspaper_id' => \App\Domain\Newspapers\Models\Newspaper::factory(),
            'forecast_date' => now()->addDay()->toDateString(),
            'suggested_quantity' => $this->faker->numberBetween(1, 50),
            'final_quantity' => null,
            'method' => $this->faker->randomElement([ForecastMethod::SAME_WEEKDAY, ForecastMethod::MOVING_AVERAGE]),
            'confidence_score' => $this->faker->randomFloat(2, 0.5, 1),
            'source_data' => ['method' => 'test'],
        ];
    }
}
