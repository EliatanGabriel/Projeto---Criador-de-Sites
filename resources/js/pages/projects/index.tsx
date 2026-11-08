import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Pagination from '@/components/pagination';
import type { Paginator } from '@/types';

const STATUS: Record<string, string> = { planning: 'Planejamento', in_progress: 'Em andamento', in_review: 'Em revisão', blocked: 'Bloqueado', completed: 'Concluído', cancelled: 'Cancelado' };

export default function ProjectsIndex({ projects, filters, clients, users }: { projects: Paginator<any>; filters: Record<string, string>; clients: { id: string; name: string }[]; users: { id: string; name: string }[] }) {
  const [search, setSearch] = useState(filters.search ?? '');
  const [status, setStatus] = useState(filters.status ?? '');
  const [clientId, setClientId] = useState(filters.client_id ?? '');
  const [owner, setOwner] = useState(filters.technical_owner_id ?? '');

  function applyFilters(e: React.FormEvent) {
    e.preventDefault();
    const p = new URLSearchParams();
    if (search) p.set('search', search);
    if (status) p.set('status', status);
    if (clientId) p.set('client_id', clientId);
    if (owner) p.set('technical_owner_id', owner);
    window.location.href = `/projects?${p.toString()}`;
  }

  return (
    <AuthenticatedLayout>
      <Head title="Projetos" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Projetos</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{projects.total} projetos</p>
        </div>
        <Link href="/projects/create">
          <Button>Converter demanda</Button>
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
            <select value={owner} onChange={(e) => setOwner(e.target.value)} className="rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
              <option value="">Todos os técnicos</option>
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
                  <th className="pb-2">Status</th>
                  <th className="pb-2">Técnico</th>
                  <th className="pb-2">Prazo</th>
                  <th className="pb-2 text-right">Ações</th>
                </tr>
              </thead>
              <tbody>
                {projects.data.length === 0 && (
                  <tr>
                    <td colSpan={6} className="py-8 text-center text-gray-500 dark:text-gray-400">
                      Nenhum projeto encontrado.
                    </td>
                  </tr>
                )}
                {projects.data.map((p: any) => (
                  <tr key={p.id} className="border-b last:border-0">
                    <td className="py-3 font-medium">{p.title}</td>
                    <td className="py-3">{p.client?.name ?? '—'}</td>
                    <td className="py-3">{STATUS[p.status] ?? p.status}</td>
                    <td className="py-3">{p.technical_owner?.name ?? '—'}</td>
                    <td className="py-3 text-gray-500 dark:text-gray-400">{p.planned_deadline ? new Date(p.planned_deadline).toLocaleDateString('pt-BR') : '—'}</td>
                    <td className="py-3 text-right">
                      <Link href={`/projects/${p.id}`} className="mr-3 text-blue-600 hover:underline">
                        Ver
                      </Link>
                      <Link href={`/projects/${p.id}/edit`} className="text-blue-600 hover:underline">
                        Editar
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="mt-4 flex justify-center">
            <Pagination links={projects.links} />
          </div>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
