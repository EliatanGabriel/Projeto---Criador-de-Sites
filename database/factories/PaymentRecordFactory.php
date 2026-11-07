<?php

namespace Database\Factories;

use App\Models\FinancialTransaction;
use App\Models\PaymentRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'transaction_id' => FinancialTransaction::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'paid_at' => now()->toDateString(),
            'method' => 'pix',
            'reference' => fake()->uuid(),
            'notes' => null,
        ];
    }
}
