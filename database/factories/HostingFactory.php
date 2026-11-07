<?php

namespace Database\Factories;

use App\Models\Hosting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Hosting>
 */
class HostingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'provider' => fake()->randomElement(['Hostinger', 'AWS', 'Render', 'Hetzner']),
            'name' => fake()->company().' Hospedagem',
            'plan' => 'Standard',
            'account_identifier' => fake()->uuid(),
            'status' => 'ativo',
            'notes' => null,
        ];
    }
}
