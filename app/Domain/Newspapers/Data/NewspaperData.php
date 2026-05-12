<?php

namespace App\Domain\Newspapers\Data;

use App\Domain\Newspapers\Enums\Language;
use App\Domain\Newspapers\Enums\NewspaperStatus;
use App\Domain\Newspapers\Enums\PublicationFrequency;
use Illuminate\Validation\Rules\Enum;

class NewspaperData
{
    public static function rules(?int $newspaperId = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'publisher_name' => 'nullable|string|max:255',
            'language' => ['nullable', new Enum(Language::class)],
            'frequency' => ['required', new Enum(PublicationFrequency::class)],
            'status' => ['required', new Enum(NewspaperStatus::class)],
            'price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'prices' => 'nullable|array',
            'prices.*.label' => 'nullable|string|max:255',
            'prices.*.price' => 'required|numeric|min:0',
            'prices.*.cost_price' => 'nullable|numeric|min:0',
        ];
    }
}
