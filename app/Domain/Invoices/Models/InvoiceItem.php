<?php

namespace App\Domain\Invoices\Models;

use App\Domain\Newspapers\Models\Newspaper;
use App\Domain\Newspapers\Models\NewspaperPrice;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'invoice_id',
    'newspaper_id',
    'price_id',
    'quantity',
    'unit_price',
    'total_price'
])]
class InvoiceItem extends Model
{
    use HasFactory, SoftDeletes;

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function newspaper(): BelongsTo
    {
        return $this->belongsTo(Newspaper::class);
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(NewspaperPrice::class);
    }
}
