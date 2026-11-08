import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

export default function Dashboard({ stats, recentOpportunities }: { stats: { active_clients: number; open_opportunities: number; in_negotiation: number; won: number; lost: number; open_estimated_total: number }; recentOpportunities: { id: string; title: string; status: string; estimated_value: string | null; created_at: string; client?: { name: string } | null }[] }) {
  const brl = (v: number) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

  return (
    <AuthenticatedLayout>
      <Head title="Dashboard" />
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Dashboard</h2>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Visão geral da operação — indicadores reais da base.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Card>
          <CardHeader>
            <CardDescription>Clientes ativos</CardDescription>
            <CardTitle className="text-3xl">{stats.active_clients}</CardTitle>
          </CardHeader>
          <CardContent>
            <Link href="/clients" className="text-sm text-blue-600 hover:underline">
              Ver clientes →
            </Link>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardDescription>Oportunidades abertas</CardDescription>
            <CardTitle className="text-3xl">{stats.open_opportunities}</CardTitle>
          </CardHeader>
          <CardContent>
            <Link href="/opportunities" className="text-sm text-blue-600 hover:underline">
              Ver oportunidades →
            </Link>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardDescription>Em negociação</CardDescription>
            <CardTitle className="text-3xl">{stats.in_negotiation}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-sm text-gray-500 dark:text-gray-400">Pipeline ativo</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardDescription>Ganhas</CardDescription>
            <CardTitle className="text-3xl text-emerald-600">{stats.won}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-sm text-gray-500 dark:text-gray-400">Vendas fechadas</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardDescription>Perdidas</CardDescription>
            <CardTitle className="text-3xl text-red-600">{stats.lost}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-sm text-gray-500 dark:text-gray-400">Perdas registradas</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardDescription>Pipeline estimado (abertas)</CardDescription>
            <CardTitle className="text-3xl">{brl(stats.open_estimated_total)}</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-sm text-gray-500 dark:text-gray-400">Soma dos valores estimados</p>
          </CardContent>
        </Card>
      </div>

      <Card className="mt-6">
        <CardHeader>
          <CardTitle>Oportunidades recentes</CardTitle>
          <CardDescription>Últimas 5 criadas na plataforma</CardDescription>
        </CardHeader>
        <CardContent>
          {recentOpportunities.length === 0 ? (
            <p className="text-sm text-gray-500 dark:text-gray-400">Nenhuma oportunidade registrada ainda.</p>
          ) : (
            <ul className="space-y-2 text-sm">
              {recentOpportunities.map((o) => (
                <li key={o.id} className="flex items-center justify-between border-b pb-2 last:border-0">
                  <div>
                    <Link href={`/opportunities/${o.id}`} className="font-medium text-blue-600 hover:underline">
                      {o.title}
                    </Link>
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                      {o.client?.name ?? 'Sem cliente'} • {o.status}
                    </p>
                  </div>
                  <span className="font-medium">{o.estimated_value ? brl(Number(o.estimated_value)) : '—'}</span>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
