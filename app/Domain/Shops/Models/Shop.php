<?php

namespace App\Domain\Shops\Models;

use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): ShopFactory
    {
        return ShopFactory::new();
    }

    protected $fillable = [
        'name',
        'owner_name',
        'phone',
        'email',
        'whatsapp_phone',
        'preferred_invoice_delivery',
        'address',
        'credit_limit',
        'opening_balance',
        'status',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'opening_balance' => 'decimal:2',
    ];

}
