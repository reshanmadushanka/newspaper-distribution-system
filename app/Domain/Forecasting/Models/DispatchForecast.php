<?php

namespace App\Domain\Forecasting\Models;

use App\Domain\Forecasting\Enums\ForecastMethod;
use App\Domain\Shops\Models\Shop;
use App\Domain\Newspapers\Models\Newspaper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchForecast extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'newspaper_id',
        'forecast_date',
        'suggested_quantity',
        'final_quantity',
        'method',
        'confidence_score',
        'source_data',
    ];

    protected $casts = [
        'forecast_date' => 'date',
        'suggested_quantity' => 'integer',
        'final_quantity' => 'integer',
        'method' => ForecastMethod::class,
        'confidence_score' => 'decimal:2',
        'source_data' => 'array',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function newspaper(): BelongsTo
    {
        return $this->belongsTo(Newspaper::class);
    }
}
