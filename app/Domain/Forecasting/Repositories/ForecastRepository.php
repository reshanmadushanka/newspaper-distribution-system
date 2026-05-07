<?php

namespace App\Domain\Forecasting\Repositories;

use App\Domain\Forecasting\Models\DispatchForecast;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ForecastRepository implements ForecastRepositoryInterface
{
    public function create(array $data): DispatchForecast
    {
        return DispatchForecast::create($data);
    }

    public function createMany(array $records): Collection
    {
        $forecasts = [];
        foreach ($records as $record) {
            $forecasts[] = DispatchForecast::create($record);
        }
        return collect($forecasts);
    }

    public function getByDateAndShop(string $forecastDate, int $shopId): Collection
    {
        return DispatchForecast::with(['shop', 'newspaper'])
            ->where('forecast_date', $forecastDate)
            ->where('shop_id', $shopId)
            ->get();
    }

    public function getByDate(string $forecastDate): Collection
    {
        return DispatchForecast::with(['shop', 'newspaper'])
            ->where('forecast_date', $forecastDate)
            ->get();
    }

    public function updateFinalQuantities(array $quantities): void
    {
        foreach ($quantities as $id => $qty) {
            DispatchForecast::where('id', $id)->update(['final_quantity' => $qty]);
        }
    }
}
