<?php

namespace App\Domain\Shops\Data;

use App\Domain\Shops\Enums\InvoiceDeliveryMethod;
use App\Domain\Shops\Enums\ShopStatus;
use Illuminate\Validation\Rules\Enum;

class ShopData
{
    public static function rules(int $shopId = 0): array
    {
        return [
            'name' => 'required|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'phone' => ['nullable', 'regex:/^07\d{8}$/'],
            'email' => 'nullable|email|max:255',
            'whatsapp_phone' => ['nullable', 'regex:/^07\d{8}$/'],
            'address' => 'nullable|string',
            'status' => ['required', new Enum(ShopStatus::class)],
        ];
    }
}
