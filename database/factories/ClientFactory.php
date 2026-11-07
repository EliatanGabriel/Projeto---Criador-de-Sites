<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'name' => fake()->company(),
            'type' => 'pj',
            'document' => fake()->unique()->cnpj(false),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'contact_name' => fake()->name(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip' => fake()->postcode(),
            'notes' => null,
            'status' => 'ativo',
            'created_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => 'inativo']);
    }

    public function pf(): static
    {
        return $this->state(fn () => ['type' => 'pf', 'document' => fake()->unique()->cpf(false)]);
    }
}
