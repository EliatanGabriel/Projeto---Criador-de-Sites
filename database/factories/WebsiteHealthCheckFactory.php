<?php

namespace Database\Factories;

use App\Models\WebsiteDomain;
use App\Models\WebsiteHealthCheck;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebsiteHealthCheckFactory extends Factory
{
    public function definition(): array
    {
        $domain = WebsiteDomain::factory()->create();

        return [
            'id' => (string) Str::uuid7(),
            'domain_id' => $domain->id,
            'website_id' => $domain->website_id,
            'environment_id' => null,
            'trigger_source' => 'scheduled',
            'ok' => true,
            'latency_ms' => 120,
            'error_code' => null,
            'checked_at' => now(),
        ];
    }
}
