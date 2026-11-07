<?php

namespace Database\Factories;

use App\Models\Credential;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Credential>
 */
class CredentialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'website_id' => Website::factory(),
            'hosting_id' => null,
            'domain_id' => null,
            'environment_id' => null,
            'type' => 'cms',
            'label' => 'Painel CMS',
            'username' => 'admin',
            'secret_encrypted' => 'segredo-seguro',
            'notes' => null,
            'last_rotated_at' => now(),
        ];
    }

    public function withSecret(string $secret = 'segredo-seguro')
    {
        return $this->state(fn () => ['secret_encrypted' => $secret]);
    }
}
