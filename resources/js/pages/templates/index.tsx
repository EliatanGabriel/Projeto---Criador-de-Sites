import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Pagination from '@/components/pagination';
import type { Paginator } from '@/types';

export default function TemplatesIndex({ templates, filters, categories }: { templates: Paginator<any>; filters: Record<string, string>; categories: string[] }) {
  const [search, setSearch] = useState(filters.search ?? '');
  const [category, setCategory] = useState(filters.category ?? '');
  const [status, setStatus] = useState(filters.status ?? '');

  function applyFilters(e: React.FormEvent) {
    e.preventDefault();
    const p = new URLSearchParams();
    if (search) p.set('search', search);
    if (category) p.set('category', category);
    if (status) p.set('status', status);
    window.location.href = `/templates?${p.toString()}`;
  }

  return (
    <AuthenticatedLayout>
      <Head title="Templates" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Templates de projeto</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{templates.total} templates</p>
        </div>
        <Link href="/templates/create">
          <Button>Novo template</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filtros</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={applyFilters} className="flex gap-3">
            <Input placeholder="Buscar nome..." value={search} onChange={(e) => setSearch(e.target.value)} className="sm:max-w-xs" />
            <select value={category} onChange={(e) => setCategory(e.target.value)} className="rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
              <option value="">Todas categorias</option>
              {categories.map((c) => (
                <option key={c} value={c}>
                  {c}
                </option>
              ))}
            </select>
            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
              <option value="">Todos</option>
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
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b text-left text-gray-600 dark:text-gray-400">
                <th className="pb-2">Nome</th>
                <th className="pb-2">Categoria</th>
                <th className="pb-2">Versão</th>
                <th className="pb-2">Status</th>
                <th className="pb-2">Projetos</th>
                <th className="pb-2 text-right">Ações</th>
              </tr>
            </thead>
            <tbody>
              {templates.data.length === 0 && (
                <tr>
                  <td colSpan={6} className="py-8 text-center text-gray-500 dark:text-gray-400">
                    Nenhum template encontrado.
                  </td>
                </tr>
              )}
              {templates.data.map((t: any) => (
                <tr key={t.id} className="border-b last:border-0">
                  <td className="py-3 font-medium">{t.name}</td>
                  <td className="py-3">{t.category}</td>
                  <td className="py-3">{t.version}</td>
                  <td className="py-3">{t.is_active ? 'Ativo' : 'Inativo'}</td>
                  <td className="py-3">{t.projects_count ?? 0}</td>
                  <td className="py-3 text-right">
                    <Link href={`/templates/${t.id}`} className="mr-3 text-blue-600 hover:underline">
                      Ver
                    </Link>
                    <Link href={`/templates/${t.id}/edit`} className="text-blue-600 hover:underline">
                      Editar
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <div className="mt-4 flex justify-center">
            <Pagination links={templates.links} />
          </div>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
