<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Demand;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'demand_id' => Demand::factory()->state(fn () => ['status' => 'approved']),
            'client_id' => Client::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'scope' => fake()->paragraph(),
            'technical_owner_id' => null,
            'started_at' => now()->toDateString(),
            'planned_deadline' => now()->addDays(60)->toDateString(),
            'completed_at' => null,
            'status' => 'planning',
            'priority' => 'medium',
            'contracted_value' => 10000,
            'internal_notes' => null,
            'blocked_reason' => null,
            'converted_by' => null,
            'converted_at' => now(),
        ];
    }
}
