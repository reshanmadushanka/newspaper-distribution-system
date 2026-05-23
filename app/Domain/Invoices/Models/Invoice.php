<?php

namespace App\Domain\Invoices\Models;

use App\Models\User;
use App\Domain\Shops\Models\Shop;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'invoice_date',
    'shop_id',
    'created_by',
    'total_amount',
    'status',
    'printed_at',
    'notes',
    'return_total_amount',
    'total_net_amount',
    'invoice_type',
])]
class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'printed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
