import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface DemandItem {
  id: string;
  title: string;
  status: string;
  priority: string;
  submitted_at: string | null;
  created_at: string;
  client?: { name: string } | null;
  commercial_owner?: { name: string } | null;
}

export default function AdminDashboard({
  stats,
  pendingDemands,
  recentDemands,
}: {
  stats: { active_clients: number; open_opportunities: number; pending_demands: number; draft_demands: number; approved_demands: number; open_estimated_total: number };
  pendingDemands: DemandItem[];
  recentDemands: DemandItem[];
}) {
  const brl = (v: number) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const date = (v: string | null) => (v ? new Date(v).toLocaleString('pt-BR') : '—');

  return (
    <AuthenticatedLayout>
      <Head title="Painel Admin" />
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Painel Admin</h2>
          <p className="mt-1 text-sm text-gray-600">Visão geral — demandas aguardando sua análise técnica.</p>
        </div>
        <Link href="/demands">
          <Button>Ver demandas</Button>
        </Link>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {[
          ['Demandas aguardando análise', stats.pending_demands, '/demands?status=submitted'],
          ['Rascunhos', stats.draft_demands, '/demands?status=draft'],
          ['Aprovadas', stats.approved_demands, '/demands?status=approved'],
          ['Clientes ativos', stats.active_clients, '/clients'],
          ['Oportunidades abertas', stats.open_opportunities, '/opportunities'],
          ['Pipeline estimado', brl(stats.open_estimated_total), '/opportunities'],
        ].map(([label, value, href]) => (
          <Card key={label as string}>
            <CardHeader>
              <CardDescription>{label}</CardDescription>
              <CardTitle className="text-3xl">{value}</CardTitle>
            </CardHeader>
            <CardContent>
              <Link href={href as string} className="text-sm text-blue-600 hover:underline">
                Abrir →
              </Link>
            </CardContent>
          </Card>
        ))}
      </div>

      <Card className="mt-6">
        <CardHeader>
          <CardTitle>Aguardando sua análise</CardTitle>
          <CardDescription>Demandas enviadas pelo comercial (submitted / under_review)</CardDescription>
        </CardHeader>
        <CardContent>
          {pendingDemands.length === 0 ? (
            <p className="text-sm text-gray-500">Nenhuma demanda aguardando análise.</p>
          ) : (
            <ul className="space-y-2 text-sm">
              {pendingDemands.map((d) => (
                <li key={d.id} className="flex items-center justify-between border-b pb-2 last:border-0">
                  <div>
                    <Link href={`/demands/${d.id}`} className="font-medium text-blue-600 hover:underline">
                      {d.title}
                    </Link>
                    <p className="text-xs text-gray-500">
                      {d.client?.name ?? 'Sem cliente'} • {d.commercial_owner?.name ?? ''} • {d.status} • {d.priority}
                    </p>
                  </div>
                  <span className="text-xs text-gray-500">{date(d.submitted_at)}</span>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>

      <Card className="mt-6">
        <CardHeader>
          <CardTitle>Demandas recentes</CardTitle>
        </CardHeader>
        <CardContent>
          {recentDemands.length === 0 ? (
            <p className="text-sm text-gray-500">Nenhuma demanda ainda.</p>
          ) : (
            <ul className="space-y-2 text-sm">
              {recentDemands.map((d) => (
                <li key={d.id} className="flex items-center justify-between border-b pb-2 last:border-0">
                  <Link href={`/demands/${d.id}`} className="font-medium text-blue-600 hover:underline">
                    {d.title}
                  </Link>
                  <span className="text-xs text-gray-500">{d.status}</span>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
