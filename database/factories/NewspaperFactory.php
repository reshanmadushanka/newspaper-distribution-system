<?php

namespace Database\Factories;

use App\Domain\Newspapers\Enums\Language;
use App\Domain\Newspapers\Enums\NewspaperStatus;
use App\Domain\Newspapers\Enums\PublicationFrequency;
use App\Domain\Newspapers\Models\Newspaper;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Newspaper>
 */
class NewspaperFactory extends Factory
{
    protected $model = Newspaper::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' ' . fake()->randomElement(['Daily', 'Times', 'Post', 'Herald', 'Gazette']),
            'publisher_name' => fake()->company(),
            'language' => fake()->randomElement(Language::cases())->value,
            'frequency' => fake()->randomElement(PublicationFrequency::cases())->value,
            'status' => NewspaperStatus::ACTIVE->value,
        ];
    }
}
