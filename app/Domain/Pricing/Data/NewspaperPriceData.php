<?php

namespace App\Domain\Pricing\Data;

class NewspaperPriceData
{
    public static function rules(int $priceId = 0): array
    {
        return [
            'newspaper_id' => 'required|integer|exists:newspapers,id',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ];
    }
}
