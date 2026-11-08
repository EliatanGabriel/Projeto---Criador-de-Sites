import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Pagination from '@/components/pagination';
import type { Paginator } from '@/types';

const STATUS: Record<string, string> = { desenvolvimento: 'Desenvolvimento', publicado: 'Publicado', suspenso: 'Suspenso', manutencao: 'Manutenção', desativado: 'Desativado' };

export default function SitesIndex({ sites, filters, clients }: { sites: Paginator<any>; filters: Record<string, string>; clients: { id: string; name: string }[] }) {
  const [search, setSearch] = useState(filters.search ?? '');
  const [status, setStatus] = useState(filters.status ?? '');
  const [clientId, setClientId] = useState(filters.client_id ?? '');

  function applyFilters(e: React.FormEvent) {
    e.preventDefault();
    const p = new URLSearchParams();
    if (search) p.set('search', search);
    if (status) p.set('status', status);
    if (clientId) p.set('client_id', clientId);
    window.location.href = `/sites?${p.toString()}`;
  }

  return (
    <AuthenticatedLayout>
      <Head title="Sites" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Sites</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{sites.total} sites sob gestão</p>
        </div>
        <Link href="/sites/create">
          <Button>Novo site</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filtros</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={applyFilters} className="flex flex-col gap-3 sm:flex-row">
            <Input placeholder="Buscar nome, cliente ou domínio..." value={search} onChange={(e) => setSearch(e.target.value)} className="sm:max-w-sm" />
            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border px-3 py-2 text-sm">
              <option value="">Todos os status</option>
              {Object.entries(STATUS).map(([v, l]) => (
                <option key={v} value={v}>
                  {l}
                </option>
              ))}
            </select>
            <select value={clientId} onChange={(e) => setClientId(e.target.value)} className="rounded-md border px-3 py-2 text-sm">
              <option value="">Todos os clientes</option>
              {clients.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name}
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
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b text-left text-gray-600 dark:text-gray-400">
                <th className="pb-2">Nome</th>
                <th className="pb-2">Cliente</th>
                <th className="pb-2">Tipo</th>
                <th className="pb-2">Status</th>
                <th className="pb-2">Domínio principal</th>
                <th className="pb-2">Produção</th>
                <th className="pb-2">Responsável</th>
                <th className="pb-2 text-right">Ações</th>
              </tr>
            </thead>
            <tbody>
              {sites.data.length === 0 && (
                <tr>
                  <td colSpan={8} className="py-8 text-center text-gray-500 dark:text-gray-400">
                    Nenhum site encontrado.
                  </td>
                </tr>
              )}
              {sites.data.map((s: any) => (
                <tr key={s.id} className="border-b last:border-0">
                  <td className="py-3 font-medium">{s.name}</td>
                  <td className="py-3">{s.client?.name ?? '—'}</td>
                  <td className="py-3">{s.type ?? '—'}</td>
                  <td className="py-3">{STATUS[s.status] ?? s.status}</td>
                  <td className="py-3">{s.primary_domain?.domain ?? '—'}</td>
                  <td className="py-3">{s.production_environment?.url ?? '—'}</td>
                  <td className="py-3">{s.technical_owner?.name ?? '—'}</td>
                  <td className="py-3 text-right">
                    <Link href={`/sites/${s.id}`} className="mr-3 text-blue-600 hover:underline">
                      Ver
                    </Link>
                    <Link href={`/sites/${s.id}/edit`} className="text-blue-600 hover:underline">
                      Editar
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <div className="mt-4 flex justify-center">
            <Pagination links={sites.links} />
          </div>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
