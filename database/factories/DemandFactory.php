<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Demand;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Demand>
 */
class DemandFactory extends Factory
{
    public function definition(): array
    {
        $client = Client::factory();

        return [
            'id' => (string) Str::uuid7(),
            'title' => fake()->sentence(4),
            'client_id' => $client,
            'opportunity_id' => null,
            'commercial_owner_id' => User::factory(),
            'service_type' => fake()->randomElement(['Site institucional', 'Landing page', 'E-commerce']),
            'description' => fake()->paragraph(),
            'contracted_scope' => fake()->paragraph(),
            'negotiated_value' => fake()->numberBetween(2000, 20000),
            'contracted_at' => now()->toDateString(),
            'desired_deadline' => now()->addDays(60)->toDateString(),
            'priority' => 'normal',
            'status' => 'draft',
            'internal_notes' => null,
            'technical_owner_id' => null,
            'submitted_at' => null,
            'reviewed_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['status' => 'submitted', 'submitted_at' => now()]);
    }

    public function underReview(): static
    {
        return $this->state(fn () => ['status' => 'under_review', 'submitted_at' => now()->subDay()]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved', 'approved_at' => now()]);
    }
}
