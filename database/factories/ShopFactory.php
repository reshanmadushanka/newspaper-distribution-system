<?php

namespace Database\Factories;

use App\Domain\Shops\Models\Shop;
use App\Domain\Shops\Enums\ShopStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'owner_name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->email(),
            'whatsapp_phone' => $this->faker->phoneNumber(),
            'preferred_invoice_delivery' => $this->faker->randomElement(['print', 'email', 'whatsapp']),
            'address' => $this->faker->address(),
            'credit_limit' => $this->faker->randomFloat(2, 0, 10000),
            'opening_balance' => $this->faker->randomFloat(2, 0, 1000),
            'status' => ShopStatus::ACTIVE,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShopStatus::ACTIVE,
        ]);
    }
}
