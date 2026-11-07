<?php

namespace Database\Factories;

use App\Models\ExpenseRecord;
use App\Models\FinancialTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ExpenseRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'transaction_id' => FinancialTransaction::factory()->create(['type' => 'despesa']),
            'vendor' => fake()->company(),
            'invoice_ref' => fake()->uuid(),
            'due_at' => now()->addDays(3)->toDateString(),
            'paid_at' => now()->toDateString(),
        ];
    }
}
