<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FinancialTransactionFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['receita', 'despesa']);

        return [
            'id' => (string) Str::uuid7(),
            'type' => $type,
            'category_id' => FinancialCategory::factory()->create(['type' => $type]),
            'client_id' => Client::factory(),
            'project_id' => null,
            'description' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 100, 10000),
            'status' => 'previsto',
            'due_at' => now()->addDays(7)->toDateString(),
            'paid_at' => null,
            'currency' => 'BRL',
            'notes' => null,
            'created_by' => null,
        ];
    }
}
