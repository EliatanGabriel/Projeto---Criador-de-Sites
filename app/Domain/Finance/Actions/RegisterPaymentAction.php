<?php

namespace App\Domain\Finance\Actions;

use App\Models\FinancialTransaction;
use App\Models\PaymentRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterPaymentAction
{
    public function __invoke(FinancialTransaction $transaction, array $data): PaymentRecord
    {
        return DB::transaction(function () use ($transaction, $data) {
            $locked = FinancialTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($locked->status === 'cancelado') {
                abort(422, 'Transação cancelada não aceita pagamentos.');
            }

            $amount = (float) $data['amount'] ?? 0;
            $amount = round((float) ($data['amount'] ?? 0), 2);

            if ($amount <= 0) {
                abort(422, 'Valor do pagamento deve ser positivo.');
            }

            $paid = (float) $locked->payments()->sum('amount');
            $total = round($paid + $amount, 2);

            if ($total - (float) $locked->amount > 0.001) {
                abort(422, 'Pagamento excede o valor total da transação.');
            }

            $payment = $locked->payments()->create([
                'amount' => $amount,
                'paid_at' => $data['paid_at'],
                'method' => $data['method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if (abs($total - (float) $locked->amount) < 0.001) {
                $locked->update([
                    'status' => $locked->type === 'receita' ? 'recebido' : 'pago',
                    'paid_at' => $data['paid_at'],
                ]);
            } elseif ($locked->status === 'previsto') {
                $locked->update(['status' => 'pendente']);
            }

            Log::info('[finance] payment registered', ['transaction' => $locked->id, 'payment' => $payment->id, 'amount' => (string) $amount]);

            return $payment;
        });
    }
}
