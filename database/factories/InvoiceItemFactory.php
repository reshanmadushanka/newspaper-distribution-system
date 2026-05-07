<?php

namespace Database\Factories;

use App\Domain\Invoices\Models\InvoiceItem;
use App\Domain\Newspapers\Models\Newspaper;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceItemFactory extends Factory
{
    protected $model = InvoiceItem::class;

    public function definition(): array
    {
        $newspaper = Newspaper::factory()->create();
        
        $quantity = $this->faker->numberBetween(1, 50);
        $unitPrice = $this->faker->randomFloat(2, 1, 20);
        
        return [
            'invoice_id' => \App\Domain\Invoices\Models\Invoice::factory(),
            'newspaper_id' => $newspaper->id,
            'newspaper_code' => $newspaper->code ?? 'NP001',
            'newspaper_name' => $newspaper->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => $quantity * $unitPrice,
            'forecast_quantity' => $quantity,
            'manual_adjustment_reason' => null,
        ];
    }
}
