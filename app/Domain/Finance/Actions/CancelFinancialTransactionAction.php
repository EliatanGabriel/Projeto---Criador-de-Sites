<?php

namespace App\Domain\Finance\Actions;

use App\Models\FinancialTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CancelFinancialTransactionAction
{
    public function __invoke(FinancialTransaction $transaction): FinancialTransaction
    {
        return DB::transaction(function () use ($transaction) {
            $locked = FinancialTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($locked->payments()->exists()) {
                abort(422, 'Transação com pagamentos registrados não pode ser cancelada.');
            }

            if ($locked->status === 'cancelado') {
                return $locked;
            }

            $locked->update(['status' => 'cancelado']);

            Log::info('[finance] transaction cancelled', ['id' => $locked->id]);

            return $locked;
        });
    }
}
