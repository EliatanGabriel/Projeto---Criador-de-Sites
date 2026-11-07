<?php

namespace Database\Factories;

use App\Models\Website;
use App\Models\WebsiteDomain;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebsiteDomain>
 */
class WebsiteDomainFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'website_id' => Website::factory(),
            'domain' => Str::lower(fake()->unique()->domainName()),
            'is_primary' => false,
            'registrar' => 'Registro.br',
            'registered_at' => now()->subYear()->toDateString(),
            'expires_at' => now()->addYear()->toDateString(),
            'status' => 'ativo',
            'dns_notes' => null,
        ];
    }
}
