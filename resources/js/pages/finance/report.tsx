import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const fmt = (v: number) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v);

export default function FinanceReport({ month, summary, custosPorProjeto }: { month: string; summary: any; custosPorProjeto: any[] }) {
  return (
    <>
      <Head title={`Relatório ${month}`} />
      <div className="mb-6 flex items-center justify-between">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Relatório financeiro — {month}</h2>
        <Link href="/finance" className="text-blue-600 hover:underline">Voltar</Link>
      </div>

      <div className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <Card><CardHeader><CardTitle>Receita prevista</CardTitle></CardHeader><CardContent>{fmt(summary.revenue_previsto ?? 0)}</CardContent></Card>
        <Card><CardHeader><CardTitle>Receita recebida</CardTitle></CardHeader><CardContent>{fmt(summary.revenue_recebido ?? 0)}</CardContent></Card>
        <Card><CardHeader><CardTitle>Receita pendente</CardTitle></CardHeader><CardContent>{fmt(summary.revenue_pendente ?? 0)}</CardContent></Card>
        <Card><CardHeader><CardTitle>Despesa paga</CardTitle></CardHeader><CardContent>{fmt(summary.despesa_paga ?? 0)}</CardContent></Card>
        <Card><CardHeader><CardTitle>Despesa prevista</CardTitle></CardHeader><CardContent>{fmt(summary.despesa_prevista ?? 0)}</CardContent></Card>
        <Card><CardHeader><CardTitle>Resultado operacional</CardTitle></CardHeader><CardContent>{fmt(summary.resultado_operacional ?? 0)}</CardContent></Card>
      </div>

      <Card>
        <CardHeader><CardTitle>Custos de despesa por projeto</CardTitle></CardHeader>
        <CardContent>
          {(custosPorProjeto ?? []).map((c: any) => (
            <p key={c.project} className="text-sm">{c.project} — {fmt(c.total)}</p>
          ))}
          {(custosPorProjeto ?? []).length === 0 && <p className="text-sm text-gray-500 dark:text-gray-400">Sem despesas de projeto no período.</p>}
        </CardContent>
      </Card>
    </>
  );
}
