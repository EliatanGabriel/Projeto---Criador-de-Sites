<?php

namespace Database\Factories;

use App\Models\Website;
use App\Models\WebsiteAlert;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebsiteAlert>
 */
class WebsiteAlertFactory extends Factory
{
    public function definition(): array
    {
        $source = 'WebsiteDomain';
        $sourceId = (string) Str::uuid7();

        return [
            'id' => (string) Str::uuid7(),
            'website_id' => Website::factory(),
            'alert_type' => 'dominio_vencendo',
            'severity' => 'warning',
            'source_type' => $source,
            'source_id' => $sourceId,
            'title' => 'Domínio vencendo',
            'description' => null,
            'due_at' => now()->addDays(10)->toDateString(),
            'fingerprint' => hash('sha256', "dominio_vencendo|{$source}|{$sourceId}|".now()->addDays(10)->toDateString()),
            'assigned_to' => null,
            'status' => 'open',
            'resolved_at' => null,
            'resolved_by' => null,
        ];
    }
}
