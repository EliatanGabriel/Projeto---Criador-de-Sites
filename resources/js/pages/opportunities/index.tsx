import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Pagination from '@/components/pagination';
import type { Paginator, Opportunity } from '@/types';

const STATUS_LABELS: Record<string, string> = {
  nova: 'Nova',
  em_contato: 'Em contato',
  em_negociacao: 'Em negociação',
  aguardando_cliente: 'Aguardando cliente',
  ganha: 'Ganha',
  perdida: 'Perdida',
  cancelada: 'Cancelada',
};

export default function OpportunitiesIndex({ opportunities, filters, users }: { opportunities: Paginator<Opportunity>; filters: Record<string, string>; users: { id: string; name: string }[] }) {
  const [search, setSearch] = useState(filters.search ?? '');
  const [status, setStatus] = useState(filters.status ?? '');
  const [responsibleId, setResponsibleId] = useState(filters.responsible_id ?? '');

  function applyFilters(e: React.FormEvent) {
    e.preventDefault();
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (status) params.set('status', status);
    if (responsibleId) params.set('responsible_id', responsibleId);
    window.location.href = `/opportunities?${params.toString()}`;
  }

  return (
    <AuthenticatedLayout>
      <Head title="Oportunidades" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Oportunidades comerciais</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{opportunities.total} oportunidades registradas</p>
        </div>
        <Link href="/opportunities/create">
          <Button>Nova oportunidade</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filtros</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={applyFilters} className="flex flex-col gap-3 sm:flex-row">
            <Input placeholder="Buscar por título, contato, descrição..." value={search} onChange={(e) => setSearch(e.target.value)} className="sm:max-w-sm" />
            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
              <option value="">Todos os status</option>
              {Object.entries(STATUS_LABELS).map(([v, l]) => (
                <option key={v} value={v}>
                  {l}
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
                  <th className="pb-2">Responsável</th>
                  <th className="pb-2">Status</th>
                  <th className="pb-2">Estimado</th>
                  <th className="pb-2">Previsão</th>
                  <th className="pb-2 text-right">Ações</th>
                </tr>
              </thead>
              <tbody>
                {opportunities.data.length === 0 && (
                  <tr>
                    <td colSpan={7} className="py-8 text-center text-gray-500 dark:text-gray-400">
                      Nenhuma oportunidade encontrada.
                    </td>
                  </tr>
                )}
                {opportunities.data.map((o) => (
                  <tr key={o.id} className="border-b last:border-0">
                    <td className="py-3 font-medium">{o.title}</td>
                    <td className="py-3">{o.client?.name ?? o.contact_name ?? '—'}</td>
                    <td className="py-3">{o.responsible?.name ?? '—'}</td>
                    <td className="py-3">{STATUS_LABELS[o.status] ?? o.status}</td>
                    <td className="py-3">{o.estimated_value ? Number(o.estimated_value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '—'}</td>
                    <td className="py-3 text-gray-500 dark:text-gray-400">{o.expected_close_at ? new Date(o.expected_close_at).toLocaleDateString('pt-BR') : '—'}</td>
                    <td className="py-3 text-right">
                      <Link href={`/opportunities/${o.id}`} className="mr-3 text-blue-600 hover:underline">
                        Ver
                      </Link>
                      <Link href={`/opportunities/${o.id}/edit`} className="text-blue-600 hover:underline">
                        Editar
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="mt-4 flex justify-center">
            <Pagination links={opportunities.links} />
          </div>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
