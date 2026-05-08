<?php

namespace App\Domain\Invoices\Models;

use App\Domain\Newspapers\Models\Newspaper;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'invoice_id',
    'newspaper_id',
    'quantity',
    'unit_price',
    'total_price'
])]
class InvoiceItem extends Model
{
    use HasFactory, SoftDeletes;
    
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    
    public function newspaper()
    {
        return $this->belongsTo(Newspaper::class);
    }
}
