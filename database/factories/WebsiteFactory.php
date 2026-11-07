<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Website>
 */
class WebsiteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'client_id' => Client::factory(),
            'name' => fake()->company().' Site',
            'description' => fake()->sentence(),
            'type' => 'institucional',
            'status' => 'desenvolvimento',
            'started_at' => now()->toDateString(),
            'published_at' => null,
            'technical_owner_id' => null,
            'technologies' => ['laravel', 'react'],
            'notes' => null,
        ];
    }
}
