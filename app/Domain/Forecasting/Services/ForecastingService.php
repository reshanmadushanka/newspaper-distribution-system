<?php

namespace App\Domain\Forecasting\Services;

use App\Domain\Forecasting\Enums\ForecastMethod;
use App\Domain\Forecasting\Repositories\ForecastRepositoryInterface;
use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Models\InvoiceItem;
use App\Domain\Shops\Models\Shop;
use App\Domain\Newspapers\Models\Newspaper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ForecastingService
{
    public function __construct(
        private ForecastRepositoryInterface $forecastRepository
    ) {}

    public function generateForecast(string $dispatchDate, ?int $shopId = null): Collection
    {
        $targetDate = \Carbon\Carbon::parse($dispatchDate);
        $weekday = $targetDate->dayOfWeek;
        
        $shops = $shopId 
            ? Shop::where('id', $shopId)->where('status', 'active')->get()
            : Shop::where('status', 'active')->get();

        $forecasts = collect();

        foreach ($shops as $shop) {
            $newspapers = $this->getActiveNewspapersForShop($shop);
            
            foreach ($newspapers as $newspaper) {
                $suggestedQty = $this->forecastForShopNewspaper(
                    $shop,
                    $newspaper,
                    $dispatchDate,
                    $weekday
                );

                $forecast = $this->forecastRepository->create([
                    'shop_id' => $shop->id,
                    'newspaper_id' => $newspaper->id,
                    'forecast_date' => $dispatchDate,
                    'suggested_quantity' => $suggestedQty,
                    'method' => ForecastMethod::SAME_WEEKDAY->value,
                    'source_data' => $this->getSourceData($shop, $newspaper, $weekday),
                ]);

                $forecasts->push($forecast);
            }
        }

        return $forecasts;
    }

    private function forecastForShopNewspaper(
        Shop $shop,
        Newspaper $newspaper,
        string $dispatchDate,
        int $weekday
    ): int {
        $historicalData = $this->getHistoricalSameWeekdaySales(
            $shop->id,
            $newspaper->id,
            $weekday,
            $dispatchDate
        );

        if ($historicalData->isEmpty()) {
            return 0;
        }

        $netSales = $historicalData->map(function ($item) {
            return $item->dispatched_qty - $item->returned_qty;
        });

        $average = (int) round($netSales->average());
        
        $confidenceScore = $this->calculateConfidence($netSales);
        
        return max(0, $average);
    }

    private function getHistoricalSameWeekdaySales(
        int $shopId,
        int $newspaperId,
        int $weekday,
        string $dispatchDate
    ): Collection {
        return DB::table('invoices as i')
            ->join('invoice_items as ii', 'i.id', '=', 'ii.invoice_id')
            ->select([
                'i.dispatch_date',
                DB::raw('ii.quantity as dispatched_qty'),
                DB::raw('0 as returned_qty'),
            ])
            ->where('i.shop_id', $shopId)
            ->where('ii.newspaper_id', $newspaperId)
            ->where('i.status', '!=', 'cancelled')
            ->whereRaw('EXTRACT(DOW FROM i.dispatch_date) = ?', [$weekday])
            ->where('i.dispatch_date', '<', $dispatchDate)
            ->orderBy('i.dispatch_date', 'desc')
            ->limit(4)
            ->get();
    }

    private function getSourceData(Shop $shop, Newspaper $newspaper, int $weekday): array
    {
        return [
            'method' => 'same_weekday',
            'weekday' => $weekday,
            'weeks_analyzed' => 4,
            'net_sales_history' => $this->getHistoricalSameWeekdaySales(
                $shop->id,
                $newspaper->id,
                $weekday,
                now()->addDay()->toDateString()
            )->toArray(),
        ];
    }

    private function calculateConfidence(Collection $netSales): float
    {
        if ($netSales->count() < 2) {
            return 0.5;
        }

        $stdDev = $this->standardDeviation($netSales->toArray());
        $mean = $netSales->average();
        
        if ($mean == 0) return 0.5;
        
        $coefficient = $stdDev / $mean;
        
        return max(0, min(1, 1 - $coefficient));
    }

    private function standardDeviation(array $numbers): float
    {
        $mean = array_sum($numbers) / count($numbers);
        $variance = array_reduce($numbers, function ($carry, $value) use ($mean) {
            return $carry + pow($value - $mean, 2);
        }, 0) / count($numbers);
        
        return sqrt($variance);
    }

    private function getActiveNewspapersForShop(Shop $shop): Collection
    {
        return Newspaper::active()->get();
    }
}
