<?php

namespace Database\Factories;

use App\Models\Website;
use App\Models\WebsiteEnvironment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebsiteEnvironment>
 */
class WebsiteEnvironmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'website_id' => Website::factory(),
            'hosting_id' => null,
            'name' => 'Produção',
            'type' => 'production',
            'url' => 'https://'.fake()->domainName(),
            'status' => 'ativo',
            'notes' => null,
        ];
    }
}
