import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Pagination from '@/components/pagination';
import type { Paginator, Client } from '@/types';

export default function ClientsIndex({ clients, filters }: { clients: Paginator<Client>; filters: Record<string, string> }) {
  const [search, setSearch] = useState(filters.search ?? '');
  const [status, setStatus] = useState(filters.status ?? '');

  function applyFilters(e: React.FormEvent) {
    e.preventDefault();
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (status) params.set('status', status);
    window.location.href = `/clients?${params.toString()}`;
  }

  return (
    <AuthenticatedLayout>
      <Head title="Clientes" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Clientes</h2>
          <p className="mt-1 text-sm text-gray-600">{clients.total} clientes cadastrados</p>
        </div>
        <Link href="/clients/create">
          <Button>Novo cliente</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filtros</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={applyFilters} className="flex flex-col gap-3 sm:flex-row">
            <Input placeholder="Buscar por nome, documento, e-mail, contato..." value={search} onChange={(e) => setSearch(e.target.value)} className="sm:max-w-sm" />
            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border border-gray-300 px-3 py-2 text-sm">
              <option value="">Todos os status</option>
              <option value="ativo">Ativo</option>
              <option value="inativo">Inativo</option>
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
                <tr className="border-b text-left text-gray-600">
                  <th className="pb-2">Nome</th>
                  <th className="pb-2">Tipo</th>
                  <th className="pb-2">E-mail</th>
                  <th className="pb-2">Telefone</th>
                  <th className="pb-2">Status</th>
                  <th className="pb-2">Cadastro</th>
                  <th className="pb-2 text-right">Ações</th>
                </tr>
              </thead>
              <tbody>
                {clients.data.length === 0 && (
                  <tr>
                    <td colSpan={7} className="py-8 text-center text-gray-500">
                      Nenhum cliente encontrado.
                    </td>
                  </tr>
                )}
                {clients.data.map((c) => (
                  <tr key={c.id} className="border-b last:border-0">
                    <td className="py-3 font-medium">{c.name}</td>
                    <td className="py-3">{c.type === 'pf' ? 'Pessoa física' : 'Pessoa jurídica'}</td>
                    <td className="py-3">{c.email ?? '—'}</td>
                    <td className="py-3">{c.phone ?? '—'}</td>
                    <td className="py-3">
                      <span className={c.status === 'ativo' ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700' : 'rounded-full bg-gray-200 px-2 py-0.5 text-xs text-gray-700'}>
                        {c.status === 'ativo' ? 'Ativo' : 'Inativo'}
                      </span>
                    </td>
                    <td className="py-3 text-gray-500">{new Date(c.created_at).toLocaleDateString('pt-BR')}</td>
                    <td className="py-3 text-right">
                      <Link href={`/clients/${c.id}`} className="mr-3 text-blue-600 hover:underline">
                        Ver
                      </Link>
                      <Link href={`/clients/${c.id}/edit`} className="text-blue-600 hover:underline">
                        Editar
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="mt-4 flex justify-center">
            <Pagination links={clients.links} />
          </div>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
