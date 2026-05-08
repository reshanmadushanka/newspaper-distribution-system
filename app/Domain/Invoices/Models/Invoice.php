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
    'status'
])]
class Invoice extends Model
{
    use HasFactory, SoftDeletes;
    
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
