<?php

namespace App\Http\Controllers;

use App\Domain\Shared\UserScope;
use App\Models\FinancialTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FinanceReportController extends Controller
{
    public function show(Request $request)
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = $request->string('month')->toString() ?: now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $yMonth = $start->format('Y-m');

        $user = $request->user();
        $base = FinancialTransaction::query()->where('status', '!=', 'cancelado');
        UserScope::financialByClient($base, $user);

        $summaryRows = (clone $base)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'receita' AND due_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as revenue_previsto,
                COALESCE(SUM(CASE WHEN type = 'receita' AND status = 'recebido' AND paid_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as revenue_recebido,
                COALESCE(SUM(CASE WHEN type = 'receita' AND status IN ('previsto', 'pendente') THEN amount ELSE 0 END), 0) as revenue_pendente,
                COALESCE(SUM(CASE WHEN type = 'despesa' AND due_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as despesa_prevista,
                COALESCE(SUM(CASE WHEN type = 'despesa' AND status = 'pago' AND paid_at BETWEEN ? AND ? THEN amount ELSE 0 END), 0) as despesa_paga
            ", [$start->toDateString(), $end->toDateString(), $start->toDateString(), $end->toDateString(), $start->toDateString(), $end->toDateString(), $start->toDateString(), $end->toDateString()])
            ->first();

        $custosQuery = DB::table('financial_transactions')
            ->join('projects', 'projects.id', '=', 'financial_transactions.project_id')
            ->where('financial_transactions.status', '=', 'pago')
            ->where('financial_transactions.paid_at', '!=', null)
            ->where('financial_transactions.paid_at', '>=', $start->toDateString())
            ->where('financial_transactions.paid_at', '<=', $end->toDateString())
            ->where('financial_transactions.type', 'despesa')
            ->whereNotNull('financial_transactions.project_id');
        if (! $user->hasRole('OWNER')) {
            $custosQuery->where(function ($q) use ($user) {
                $q->where('projects.technical_owner_id', $user->id)
                    ->orWhereExists(function ($sub) use ($user) {
                        $sub->selectRaw(1)->from('demands')->whereColumn('demands.client_id', 'financial_transactions.client_id')->where('demands.commercial_owner_id', $user->id);
                    });
            });
        }
        $custosPorProjeto = $custosQuery
            ->groupBy('financial_transactions.project_id', 'projects.title')
            ->select('financial_transactions.project_id as project_id', 'projects.title as project', DB::raw('SUM(financial_transactions.amount) as total'))
            ->get()
            ->map(fn ($row) => ['project' => $row->project, 'total' => (float) $row->total])
            ->values();

        $revenueRecebido = (float) ($summaryRows->revenue_recebido ?? 0);
        $despesaPaga = (float) ($summaryRows->despesa_paga ?? 0);

        return Inertia::render('finance/report', [
            'month' => $yMonth,
            'summary' => [
                'revenue_previsto' => (float) ($summaryRows->revenue_previsto ?? 0),
                'revenue_recebido' => $revenueRecebido,
                'revenue_pendente' => (float) ($summaryRows->revenue_pendente ?? 0),
                'despesa_prevista' => (float) ($summaryRows->despesa_prevista ?? 0),
                'despesa_paga' => $despesaPaga,
                'resultado_operacional' => $revenueRecebido - $despesaPaga,
            ],
            'custosPorProjeto' => $custosPorProjeto,
        ]);
    }
}
