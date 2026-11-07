<?php

namespace Database\Factories;

use App\Models\Website;
use App\Models\WebsiteEnvironment;
use App\Models\WebsitePublication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebsitePublication>
 */
class WebsitePublicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'website_id' => Website::factory(),
            'environment_id' => WebsiteEnvironment::factory(),
            'version' => '1.0.0',
            'published_at' => now(),
            'responsible_id' => null,
            'description' => null,
            'commit_reference' => null,
            'result' => 'sucesso',
            'notes' => null,
        ];
    }
}
