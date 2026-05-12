<?php

namespace App\Domain\Newspapers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewspaperPrice extends Model
{
    protected $fillable = [
        'newspaper_id',
        'label',
        'price',
        'cost_price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];

    public function newspaper(): BelongsTo
    {
        return $this->belongsTo(Newspaper::class);
    }
}
