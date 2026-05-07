<?php

namespace App\Domain\Invoices\Models;

use App\Domain\Invoices\Enums\InvoiceStatus;
use App\Domain\Shops\Models\Shop;
use App\Domain\Newspapers\Models\Newspaper;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }

    protected $fillable = [
        'invoice_no',
        'shop_id',
        'route_id',
        'invoice_date',
        'dispatch_date',
        'status',
        'gross_total',
        'return_total',
        'net_total',
        'paid_total',
        'balance_total',
        'notes',
        'prepared_by',
        'confirmed_by',
        'confirmed_at',
        'printed_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'dispatch_date' => 'date',
        'status' => InvoiceStatus::class,
        'gross_total' => 'decimal:2',
        'return_total' => 'decimal:2',
        'net_total' => 'decimal:2',
        'paid_total' => 'decimal:2',
        'balance_total' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(InvoiceDelivery::class);
    }

    public function isDraft(): bool
    {
        return $this->status === InvoiceStatus::DRAFT;
    }

    public function isConfirmed(): bool
    {
        return $this->status === InvoiceStatus::CONFIRMED;
    }

    public function isLocked(): bool
    {
        return !$this->isDraft();
    }
}
