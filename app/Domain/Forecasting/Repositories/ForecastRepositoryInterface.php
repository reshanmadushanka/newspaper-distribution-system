<?php

namespace App\Domain\Forecasting\Repositories;

use App\Domain\Forecasting\Models\DispatchForecast;
use Illuminate\Support\Collection;

interface ForecastRepositoryInterface
{
    public function create(array $data): DispatchForecast;
    public function createMany(array $records): Collection;
    public function getByDateAndShop(string $forecastDate, int $shopId): Collection;
    public function getByDate(string $forecastDate): Collection;
    public function updateFinalQuantities(array $quantities): void;
}
