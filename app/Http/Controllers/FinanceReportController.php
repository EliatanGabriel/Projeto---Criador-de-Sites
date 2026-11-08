<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class FinanceReportController extends Controller
{
    public function show(Request $request)
    {
        $month = $request->string('month')->toString() ?: now()->toDateString();
        $yMonth = substr($month, 0, 7);
        $start = Carbon::parse($yMonth.'-01');
        $end = $start->copy()->endOfMonth();

        $summaryRows = FinancialTransaction::query()
            ->where('due_at', '>=', $start->toDateString())
            ->where('due_at', '<=', $end->toDateString())
            ->where('status', '!=', 'cancelado')
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'receita' THEN amount ELSE 0 END), 0) as revenue_previsto,
                COALESCE(SUM(CASE WHEN type = 'receita' AND status = 'recebido' THEN amount ELSE 0 END), 0) as revenue_recebido,
                COALESCE(SUM(CASE WHEN type = 'receita' AND status IN ('previsto', 'pendente') THEN amount ELSE 0 END), 0) as revenue_pendente,
                COALESCE(SUM(CASE WHEN type = 'despesa' THEN amount ELSE 0 END), 0) as despesa_prevista,
                COALESCE(SUM(CASE WHEN type = 'despesa' AND status = 'pago' THEN amount ELSE 0 END), 0) as despesa_paga
            ")
            ->first();

        $custosPorProjeto = DB::table('financial_transactions')
            ->join('projects', 'projects.id', '=', 'financial_transactions.project_id')
            ->where('financial_transactions.due_at', '>=', $start->toDateString())
            ->where('financial_transactions.due_at', '<=', $end->toDateString())
            ->where('financial_transactions.status', '!=', 'cancelado')
            ->where('financial_transactions.type', 'despesa')
            ->whereNotNull('financial_transactions.project_id')
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
