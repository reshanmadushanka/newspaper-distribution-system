<?php

namespace Database\Factories;

use App\Domain\Invoices\Models\Invoice;
use App\Domain\Invoices\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'invoice_no' => 'INV-' . now()->format('Ymd') . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'shop_id' => \App\Domain\Shops\Models\Shop::factory(),
            'invoice_date' => now()->toDateString(),
            'dispatch_date' => now()->addDay()->toDateString(),
            'status' => InvoiceStatus::DRAFT,
            'gross_total' => $this->faker->randomFloat(2, 10, 1000),
            'return_total' => 0,
            'net_total' => $this->faker->randomFloat(2, 10, 1000),
            'paid_total' => 0,
            'balance_total' => $this->faker->randomFloat(2, 10, 1000),
            'prepared_by' => \App\Models\User::factory(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::CONFIRMED,
            'confirmed_by' => \App\Models\User::factory(),
            'confirmed_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => InvoiceStatus::DRAFT,
        ]);
    }
}
