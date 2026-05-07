<?php

namespace App\Domain\Invoices\Models;

use App\Domain\Invoices\Enums\InvoiceDeliveryChannel;
use App\Domain\Invoices\Enums\InvoiceDeliveryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'channel',
        'recipient',
        'status',
        'provider',
        'provider_message_id',
        'error_message',
        'sent_by',
        'sent_at',
        'delivered_at',
        'metadata',
    ];

    protected $casts = [
        'channel' => InvoiceDeliveryChannel::class,
        'status' => InvoiceDeliveryStatus::class,
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
