<?php

namespace Database\Factories;

use App\Models\Website;
use App\Models\WebsiteRepository;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebsiteRepository>
 */
class WebsiteRepositoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'website_id' => Website::factory(),
            'environment_id' => null,
            'provider' => 'github',
            'name' => fake()->slug(),
            'url' => 'https://github.com/'.fake()->userName().'/'.fake()->slug(),
            'default_branch' => 'main',
            'notes' => null,
        ];
    }
}
