import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Pagination from '@/components/pagination';
import type { Paginator } from '@/types';

const STATUS: Record<string, string> = {
  draft: 'Rascunho',
  submitted: 'Enviada',
  under_review: 'Em análise',
  needs_information: 'Aguardando informações',
  approved: 'Aprovada',
  rejected: 'Recusada',
  cancelled: 'Cancelada',
};

export default function DemandsIndex({ demands, filters, clients, users }: { demands: Paginator<any>; filters: Record<string, string>; clients: { id: string; name: string }[]; users: { id: string; name: string }[] }) {
  const [search, setSearch] = useState(filters.search ?? '');
  const [status, setStatus] = useState(filters.status ?? '');
  const [clientId, setClientId] = useState(filters.client_id ?? '');
  const [responsibleId, setResponsibleId] = useState(filters.responsible_id ?? '');
  const [period, setPeriod] = useState(filters.period ?? '');

  function applyFilters(e: React.FormEvent) {
    e.preventDefault();
    const p = new URLSearchParams();
    if (search) p.set('search', search);
    if (status) p.set('status', status);
    if (clientId) p.set('client_id', clientId);
    if (responsibleId) p.set('responsible_id', responsibleId);
    if (period) p.set('period', period);
    window.location.href = `/demands?${p.toString()}`;
  }

  return (
    <>
      <Head title="Demandas" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Demandas</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{demands.total} demandas registradas</p>
        </div>
        <Link href="/demands/create">
          <Button>Nova demanda</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filtros</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={applyFilters} className="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <Input placeholder="Buscar por título ou cliente..." value={search} onChange={(e) => setSearch(e.target.value)} className="sm:max-w-sm" />
            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
              <option value="">Todos os status</option>
              {Object.entries(STATUS).map(([v, l]) => (
                <option key={v} value={v}>
                  {l}
                </option>
              ))}
            </select>
            <select value={clientId} onChange={(e) => setClientId(e.target.value)} className="rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
              <option value="">Todos os clientes</option>
              {clients.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
                </option>
              ))}
            </select>
            <select value={responsibleId} onChange={(e) => setResponsibleId(e.target.value)} className="rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
              <option value="">Todos os responsáveis</option>
              {users.map((u) => (
                <option key={u.id} value={u.id}>
                  {u.name}
                </option>
              ))}
            </select>
            <select value={period} onChange={(e) => setPeriod(e.target.value)} className="rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
              <option value="">Período</option>
              <option value="7d">Últimos 7 dias</option>
              <option value="30d">Últimos 30 dias</option>
              <option value="90d">Últimos 90 dias</option>
            </select>
            <Button type="submit" variant="secondary">
              Filtrar
            </Button>
          </form>
        </CardContent>
      </Card>

      <Card className="mt-4">
        <CardContent className="pt-6">
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-gray-600 dark:text-gray-400">
                  <th className="pb-2">Título</th>
                  <th className="pb-2">Cliente</th>
                  <th className="pb-2">Status</th>
                  <th className="pb-2">Comercial</th>
                  <th className="pb-2">Prazo desejado</th>
                  <th className="pb-2">Atualização</th>
                  <th className="pb-2 text-right">Ações</th>
                </tr>
              </thead>
              <tbody>
                {demands.data.length === 0 && (
                  <tr>
                    <td colSpan={7} className="py-8 text-center text-gray-500 dark:text-gray-400">
                      Nenhuma demanda encontrada.
                    </td>
                  </tr>
                )}
                {demands.data.map((d: any) => (
                  <tr key={d.id} className="border-b last:border-0">
                    <td className="py-3 font-medium">{d.title}</td>
                    <td className="py-3">{d.client?.name ?? '—'}</td>
                    <td className="py-3">{STATUS[d.status] ?? d.status}</td>
                    <td className="py-3">{d.commercial_owner?.name ?? '—'}</td>
                    <td className="py-3 text-gray-500 dark:text-gray-400">{d.desired_deadline ? new Date(d.desired_deadline).toLocaleDateString('pt-BR') : '—'}</td>
                    <td className="py-3 text-gray-500 dark:text-gray-400">{new Date(d.updated_at).toLocaleDateString('pt-BR')}</td>
                    <td className="py-3 text-right">
                      <Link href={`/demands/${d.id}`} className="mr-3 text-blue-600 hover:underline">
                        Ver
                      </Link>
                      <Link href={`/demands/${d.id}/edit`} className="text-blue-600 hover:underline">
                        Editar
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="mt-4 flex justify-center">
            <Pagination links={demands.links} />
          </div>
        </CardContent>
      </Card>
    </>
  );
}
