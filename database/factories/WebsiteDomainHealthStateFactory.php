<?php

namespace Database\Factories;

use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Models\WebsiteDomainHealthState;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebsiteDomainHealthStateFactory extends Factory
{
    public function definition(): array
    {
        $domain = WebsiteDomain::factory()->create();

        return [
            'id' => (string) Str::uuid7(),
            'domain_id' => $domain->id,
            'website_id' => $domain->website_id,
            'environment_id' => null,
            'state' => 'operational',
            'cycle_id' => null,
            'cycle_number' => 0,
            'cycle_started_at' => null,
            'consecutive_failures' => 0,
            'last_applied_run_id' => null,
            'last_checked_at' => null,
            'last_ok_at' => null,
            'last_recovered_at' => null,
        ];
    }
}
