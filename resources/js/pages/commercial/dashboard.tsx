import { Head, Link } from '@inertiajs/react';
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
}

export default function CommercialDashboard({
  stats,
  demands,
}: {
  stats: { draft: number; submitted: number; needs_information: number; approved: number; total: number };
  demands: DemandItem[];
}) {
  return (
    <>
      <Head title="Painel Comercial" />
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Painel Comercial</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Envie demandas para análise técnica do admin.</p>
        </div>
        <Link href="/demands/create">
          <Button>+ Nova demanda</Button>
        </Link>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {[
          ['Rascunhos', stats.draft, '/demands?status=draft'],
          ['Enviadas / em análise', stats.submitted, '/demands?status=submitted'],
          ['Precisam de informação', stats.needs_information, '/demands?status=needs_information'],
          ['Aprovadas', stats.approved, '/demands?status=approved'],
        ].map(([label, value, href]) => (
          <Card key={label as string}>
            <CardHeader>
              <CardDescription>{label}</CardDescription>
              <CardTitle className="text-3xl">{value}</CardTitle>
            </CardHeader>
            <CardContent>
              <Link href={href as string} className="text-sm text-blue-600 hover:underline">
                Ver →
              </Link>
            </CardContent>
          </Card>
        ))}
      </div>

      <Card className="mt-6">
        <CardHeader>
          <CardTitle>Minhas demandas recentes</CardTitle>
          <CardDescription>{stats.total} no total</CardDescription>
        </CardHeader>
        <CardContent>
          {demands.length === 0 ? (
            <p className="text-sm text-gray-500 dark:text-gray-400">
              Nenhuma demanda ainda.{' '}
              <Link href="/demands/create" className="text-blue-600 hover:underline">
                Criar a primeira →
              </Link>
            </p>
          ) : (
            <ul className="space-y-2 text-sm">
              {demands.map((d) => (
                <li key={d.id} className="flex items-center justify-between border-b pb-2 last:border-0">
                  <div>
                    <Link href={`/demands/${d.id}`} className="font-medium text-blue-600 hover:underline">
                      {d.title}
                    </Link>
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                      {d.client?.name ?? 'Sem cliente'} • {d.status} • {d.priority}
                    </p>
                  </div>
                  <span className="text-xs text-gray-500 dark:text-gray-400">{d.submitted_at ? new Date(d.submitted_at).toLocaleString('pt-BR') : '—'}</span>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>
    </>
  );
}
