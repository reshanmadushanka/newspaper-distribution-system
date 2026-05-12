<?php

namespace App\Domain\Invoices\Data;

class InvoiceData
{
    public static function rules(): array
    {
        return [
            'invoice_date' => 'required|date',
            'shop_id' => 'required|exists:shops,id',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.newspaper_id' => 'required|exists:newspapers,id',
            'items.*.price_id' => 'required|exists:newspaper_prices,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ];
    }
}
