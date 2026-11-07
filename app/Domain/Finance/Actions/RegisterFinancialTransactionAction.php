<?php

namespace App\Domain\Finance\Actions;

use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterFinancialTransactionAction
{
    public function __invoke(array $data): FinancialTransaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = FinancialTransaction::create([
                'type' => $data['type'],
                'category_id' => $data['category_id'],
                'client_id' => $data['client_id'] ?? null,
                'project_id' => $data['project_id'] ?? null,
                'description' => $data['description'],
                'amount' => $data['amount'],
                'status' => $data['status'] ?? 'previsto',
                'due_at' => $data['due_at'] ?? null,
                'paid_at' => $data['paid_at'] ?? null,
                'currency' => 'BRL',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            Log::info('[finance] transaction created', ['id' => $transaction->id, 'type' => $transaction->type, 'amount' => (string) $transaction->amount]);

            return $transaction;
        });
    }
}
