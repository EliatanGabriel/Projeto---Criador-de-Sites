import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Pagination from '@/components/pagination';
import type { Paginator } from '@/types';

const T_STATUS: Record<string, string> = { todo: 'A fazer', in_progress: 'Em andamento', in_review: 'Em revisão', blocked: 'Bloqueada', done: 'Concluída', cancelled: 'Cancelada' };

export default function MyTasks({ tasks, filters }: { tasks: Paginator<any>; filters: Record<string, string> }) {
  const [status, setStatus] = useState(filters.status ?? '');
  const [priority, setPriority] = useState(filters.priority ?? '');

  function applyFilters(e: React.FormEvent) {
    e.preventDefault();
    const p = new URLSearchParams();
    if (status) p.set('status', status);
    if (priority) p.set('priority', priority);
    window.location.href = `/tasks/my?${p.toString()}`;
  }

  return (
    <>
      <Head title="Minhas tarefas" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">Minhas tarefas</h2>

      <Card>
        <CardHeader>
          <CardTitle>Filtros</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={applyFilters} className="flex gap-3">
            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
              <option value="">Todos os status</option>
              {Object.entries(T_STATUS).map(([v, l]) => (
                <option key={v} value={v}>
                  {l}
                </option>
              ))}
            </select>
            <select value={priority} onChange={(e) => setPriority(e.target.value)} className="rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
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
              <tr className="border-b text-left text-gray-600 dark:text-gray-400">
                <th className="pb-2">Tarefa</th>
                <th className="pb-2">Projeto</th>
                <th className="pb-2">Status</th>
                <th className="pb-2">Prazo</th>
                <th className="pb-2 text-right">Ações</th>
              </tr>
            </thead>
            <tbody>
              {tasks.data.length === 0 && (
                <tr>
                  <td colSpan={5} className="py-8 text-center text-gray-500 dark:text-gray-400">
                    Nenhuma tarefa atribuída a você.
                  </td>
                </tr>
              )}
              {tasks.data.map((t: any) => (
                <tr key={t.id} className="border-b last:border-0">
                  <td className="py-3 font-medium">{t.title}</td>
                  <td className="py-3">{t.project?.title ?? '—'}</td>
                  <td className="py-3">{T_STATUS[t.status] ?? t.status}</td>
                  <td className="py-3 text-gray-500 dark:text-gray-400">{t.due_at ? new Date(t.due_at).toLocaleDateString('pt-BR') : '—'}</td>
                  <td className="py-3 text-right">
                    <Link href={`/tasks/${t.id}`} className="text-blue-600 hover:underline">
                      Ver
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
    </>
  );
}
