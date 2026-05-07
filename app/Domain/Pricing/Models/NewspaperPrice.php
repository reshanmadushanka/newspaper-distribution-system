<?php

namespace App\Domain\Pricing\Models;

use App\Domain\Newspapers\Models\Newspaper;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewspaperPrice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'newspaper_id',
        'price',
        'cost_price',
        'effective_from',
        'effective_to',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'created_at' => 'datetime',
    ];

    public function newspaper(): BelongsTo
    {
        return $this->belongsTo(Newspaper::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCurrentPrice(): bool
    {
        return $this->effective_to === null;
    }
}
