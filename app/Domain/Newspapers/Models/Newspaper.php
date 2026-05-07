<?php

namespace App\Domain\Newspapers\Models;

use App\Domain\Newspapers\Enums\Language;
use App\Domain\Newspapers\Enums\NewspaperStatus;
use App\Domain\Newspapers\Enums\PublicationFrequency;
use App\Domain\Pricing\Models\NewspaperPrice;
use Database\Factories\NewspaperFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Newspaper extends Model
{
    use HasFactory, SoftDeletes;

    protected static function newFactory(): NewspaperFactory
    {
        return NewspaperFactory::new();
    }

    protected $fillable = [
        'name',
        'publisher_name',
        'language',
        'frequency',
        'status',
    ];

    protected $casts = [
        'language' => Language::class,
        'frequency' => PublicationFrequency::class,
        'status' => NewspaperStatus::class,
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(NewspaperPrice::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', NewspaperStatus::ACTIVE);
    }
}
