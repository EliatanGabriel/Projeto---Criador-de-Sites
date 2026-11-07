import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Pagination from '@/components/pagination';
import type { Paginator } from '@/types';

const T_STATUS: Record<string, string> = { todo: 'A fazer', in_progress: 'Em andamento', in_review: 'Em revisão', blocked: 'Bloqueada', done: 'Concluída', cancelled: 'Cancelada' };

export default function TasksIndex({ tasks, filters, projects, users }: { tasks: Paginator<any>; filters: Record<string, string>; projects: { id: string; title: string }[]; users: { id: string; name: string }[] }) {
  const [search, setSearch] = useState(filters.search ?? '');
  const [projectId, setProjectId] = useState(filters.project_id ?? '');
  const [status, setStatus] = useState(filters.status ?? '');
  const [priority, setPriority] = useState(filters.priority ?? '');

  function applyFilters(e: React.FormEvent) {
    e.preventDefault();
    const p = new URLSearchParams();
    if (search) p.set('search', search);
    if (projectId) p.set('project_id', projectId);
    if (status) p.set('status', status);
    if (priority) p.set('priority', priority);
    window.location.href = `/tasks?${p.toString()}`;
  }

  return (
    <AuthenticatedLayout>
      <Head title="Tarefas" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">Tarefas</h2>
          <p className="mt-1 text-sm text-gray-600">{tasks.total} tarefas</p>
        </div>
        <div className="flex gap-2">
          <Link href="/tasks/my">
            <Button variant="secondary">Minhas tarefas</Button>
          </Link>
          <Link href="/tasks/create">
            <Button>Nova tarefa</Button>
          </Link>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filtros</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={applyFilters} className="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
            <Input placeholder="Buscar título..." value={search} onChange={(e) => setSearch(e.target.value)} className="sm:max-w-xs" />
            <select value={projectId} onChange={(e) => setProjectId(e.target.value)} className="rounded-md border border-gray-300 px-3 py-2 text-sm">
              <option value="">Todos os projetos</option>
              {projects.map((p: any) => (
                <option key={p.id} value={p.id}>
                  {p.title}
                </option>
              ))}
            </select>
            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border border-gray-300 px-3 py-2 text-sm">
              <option value="">Todos os status</option>
              {Object.entries(T_STATUS).map(([v, l]) => (
                <option key={v} value={v}>
                  {l}
                </option>
              ))}
            </select>
            <select value={priority} onChange={(e) => setPriority(e.target.value)} className="rounded-md border border-gray-300 px-3 py-2 text-sm">
              <option value="">Todas prioridades</option>
              <option value="low">Baixa</option>
              <option value="medium">Normal</option>
              <option value="high">Alta</option>
              <option value="urgent">Urgente</option>
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
                <th className="pb-2">Título</th>
                <th className="pb-2">Projeto</th>
                <th className="pb-2">Responsável</th>
                <th className="pb-2">Status</th>
                <th className="pb-2">Prioridade</th>
                <th className="pb-2">Prazo</th>
                <th className="pb-2 text-right">Ações</th>
              </tr>
            </thead>
            <tbody>
              {tasks.data.length === 0 && (
                <tr>
                  <td colSpan={7} className="py-8 text-center text-gray-500">
                    Nenhuma tarefa encontrada.
                  </td>
                </tr>
              )}
              {tasks.data.map((t: any) => (
                <tr key={t.id} className="border-b last:border-0">
                  <td className="py-3 font-medium">{t.title}</td>
                  <td className="py-3">{t.project?.title ?? '—'}</td>
                  <td className="py-3">{t.assignee?.name ?? '—'}</td>
                  <td className="py-3">{T_STATUS[t.status] ?? t.status}</td>
                  <td className="py-3">{t.priority}</td>
                  <td className="py-3 text-gray-500">{t.due_at ? new Date(t.due_at).toLocaleDateString('pt-BR') : '—'}</td>
                  <td className="py-3 text-right">
                    <Link href={`/tasks/${t.id}`} className="mr-3 text-blue-600 hover:underline">
                      Ver
                    </Link>
                    <Link href={`/tasks/${t.id}/edit`} className="text-blue-600 hover:underline">
                      Editar
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <div className="mt-4 flex justify-center">
            <Pagination links={tasks.links} />
          </div>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
