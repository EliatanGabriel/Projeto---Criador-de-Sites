<?php

namespace Database\Factories;

use App\Models\ProjectTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProjectTemplate>
 */
class ProjectTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'category' => 'Site institucional',
            'version' => '1.0',
            'is_active' => true,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
