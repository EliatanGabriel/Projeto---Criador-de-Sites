<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinanceReportController extends Controller
{
    public function show(Request $request)
    {
        $month = $request->string('month')->toString() ?: now()->toDateString();
        $yMonth = substr($month, 0, 7);
        $start = Carbon::parse($yMonth.'-01');
        $end = $start->copy()->endOfMonth();

        $transactions = FinancialTransaction::query()
            ->where('due_at', '>=', $start->toDateString())
            ->where('due_at', '<=', $end->toDateString())
            ->where('status', '!=', 'cancelado')
            ->with(['client:id,name', 'project:id,title', 'payments'])
            ->get();

        $revenuePrevisto = $transactions->where('type', 'receita')->sum('amount');
        $revenueRecebido = $transactions->where('type', 'receita')->where('status', 'recebido')->sum('amount');
        $revenuePendente = $transactions->where('type', 'receita')->whereIn('status', ['previsto', 'pendente'])->sum('amount');
        $despesaPrevista = $transactions->where('type', 'despesa')->sum('amount');
        $despesaPaga = $transactions->where('type', 'despesa')->where('status', 'pago')->sum('amount');
        $resultado = $revenueRecebido - $despesaPaga;

        $custosPorProjeto = $transactions
            ->where('type', 'despesa')
            ->whereNotNull('project_id')
            ->groupBy('project_id')
            ->map(fn ($items, $projectId) => [
                'project' => $items->first()->project->title ?? '—',
                'total' => (float) $items->sum('amount'),
            ])
            ->values();

        return Inertia::render('finance/report', [
            'month' => $yMonth,
            'summary' => [
                'revenue_previsto' => (float) $revenuePrevisto,
                'revenue_recebido' => (float) $revenueRecebido,
                'revenue_pendente' => (float) $revenuePendente,
                'despesa_prevista' => (float) $despesaPrevista,
                'despesa_paga' => (float) $despesaPaga,
                'resultado_operacional' => (float) $resultado,
            ],
            'custosPorProjeto' => $custosPorProjeto,
            'transactions' => $transactions,
        ]);
    }
}
