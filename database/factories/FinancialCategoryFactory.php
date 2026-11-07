<?php

namespace Database\Factories;

use App\Models\FinancialCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FinancialCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'name' => fake()->word(),
            'type' => fake()->randomElement(['receita', 'despesa']),
            'is_system' => false,
        ];
    }
}
