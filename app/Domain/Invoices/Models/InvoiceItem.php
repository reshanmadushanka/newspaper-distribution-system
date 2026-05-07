<?php

namespace App\Domain\Invoices\Models;

use App\Domain\Newspapers\Models\Newspaper;
use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected static function newFactory(): InvoiceItemFactory
    {
        return InvoiceItemFactory::new();
    }

    protected $fillable = [
        'invoice_id',
        'newspaper_id',
        'newspaper_code',
        'newspaper_name',
        'quantity',
        'unit_price',
        'line_total',
        'forecast_quantity',
        'manual_adjustment_reason',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'forecast_quantity' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function newspaper(): BelongsTo
    {
        return $this->belongsTo(Newspaper::class);
    }
}
