import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Pagination from '@/components/pagination';
import type { Paginator } from '@/types';

export default function HostingsIndex({ hostings, filters }: { hostings: Paginator<any>; filters: Record<string, string> }) {
  const [search, setSearch] = useState(filters.search ?? '');
  const [status, setStatus] = useState(filters.status ?? '');

  function applyFilters(e: React.FormEvent) {
    e.preventDefault();
    const p = new URLSearchParams();
    if (search) p.set('search', search);
    if (status) p.set('status', status);
    window.location.href = `/hostings?${p.toString()}`;
  }

  return (
    <AuthenticatedLayout>
      <Head title="Hospedagens" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Hospedagens</h2>
          <p className="mt-1 text-sm text-gray-600">{hostings.total} hospedagens</p>
        </div>
        <Link href="/hostings/create">
          <Button>Nova hospedagem</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filtros</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={applyFilters} className="flex gap-3">
            <Input placeholder="Buscar nome ou provedor..." value={search} onChange={(e) => setSearch(e.target.value)} className="sm:max-w-xs" />
            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border px-3 py-2 text-sm">
              <option value="">Todos</option>
              <option value="ativo">Ativo</option>
              <option value="suspenso">Suspenso</option>
              <option value="cancelado">Cancelado</option>
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
              <tr className="border-b text-left text-gray-600">
                <th className="pb-2">Nome</th>
                <th className="pb-2">Provedor</th>
                <th className="pb-2">Plano</th>
                <th className="pb-2">Status</th>
                <th className="pb-2 text-right">Ações</th>
              </tr>
            </thead>
            <tbody>
              {hostings.data.length === 0 && (
                <tr>
                  <td colSpan={5} className="py-8 text-center text-gray-500">
                    Nenhuma hospedagem encontrada.
                  </td>
                </tr>
              )}
              {hostings.data.map((h: any) => (
                <tr key={h.id} className="border-b last:border-0">
                  <td className="py-3 font-medium">{h.name}</td>
                  <td className="py-3">{h.provider}</td>
                  <td className="py-3">{h.plan ?? '—'}</td>
                  <td className="py-3">{h.status}</td>
                  <td className="py-3 text-right">
                    <Link href={`/hostings/${h.id}`} className="mr-3 text-blue-600 hover:underline">
                      Ver
                    </Link>
                    <Link href={`/hostings/${h.id}/edit`} className="text-blue-600 hover:underline">
                      Editar
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <div className="mt-4 flex justify-center">
            <Pagination links={hostings.links} />
          </div>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
