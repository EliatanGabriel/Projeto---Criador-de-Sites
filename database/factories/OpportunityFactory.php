<?php

namespace Database\Factories;

use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'title' => fake()->sentence(4),
            'client_id' => null,
            'contact_name' => fake()->name(),
            'responsible_id' => User::factory(),
            'service_type' => fake()->randomElement(['Site institucional', 'Landing page', 'E-commerce', 'Catálogo']),
            'description' => fake()->paragraph(),
            'estimated_value' => fake()->numberBetween(2000, 20000),
            'negotiated_value' => null,
            'opened_at' => now()->toDateString(),
            'expected_close_at' => now()->addDays(30)->toDateString(),
            'status' => 'nova',
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }

    public function won(): static
    {
        return $this->state(fn () => ['status' => 'ganha']);
    }

    public function lost(): static
    {
        return $this->state(fn () => ['status' => 'perdida']);
    }
}
