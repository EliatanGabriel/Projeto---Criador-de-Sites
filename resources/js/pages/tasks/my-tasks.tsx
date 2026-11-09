import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Pagination from '@/components/pagination';
import type { Paginator } from '@/types';

const COLUMNS = [
  { key: 'todo', label: 'A fazer', color: 'bg-gray-400' },
  { key: 'in_progress', label: 'Em andamento', color: 'bg-blue-500' },
  { key: 'in_review', label: 'Em revisão', color: 'bg-amber-500' },
  { key: 'blocked', label: 'Bloqueadas', color: 'bg-red-500' },
  { key: 'done', label: 'Concluídas', color: 'bg-green-500' },
] as const;

type StatusKey = (typeof COLUMNS)[number]['key'];

const T_STATUS: Record<string, string> = { todo: 'A fazer', in_progress: 'Em andamento', in_review: 'Em revisão', blocked: 'Bloqueada', done: 'Concluída', cancelled: 'Cancelada' };

interface TaskCard {
  id: string;
  title: string;
  status: string;
  priority: string;
  due_at: string | null;
  blocked_reason?: string | null;
  project?: { title: string } | null;
}

function priorityBadge(priority: string) {
  const styles: Record<string, string> = {
    urgent: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    high: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
    medium: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
    low: 'bg-gray-50 text-gray-400 dark:bg-gray-800 dark:text-gray-500',
  };
  return <span className={`inline-flex rounded-full px-2 py-0.5 text-[10px] font-medium ${styles[priority] ?? styles.medium}`}>{priority}</span>;
}

export default function MyTasks({
  tasks,
  board,
  filters,
}: {
  tasks: Paginator<any>;
  board: Record<string, TaskCard[]> | TaskCard[];
  filters: Record<string, string>;
}) {
  const [status, setStatus] = useState(filters.status ?? '');
  const [priority, setPriority] = useState(filters.priority ?? '');
  const [view, setView] = useState<'kanban' | 'list'>('kanban');
  const [dragId, setDragId] = useState<string | null>(null);
  const [overCol, setOverCol] = useState<StatusKey | null>(null);
  const [blockedFor, setBlockedFor] = useState<string | null>(null);
  const [blockedReason, setBlockedReason] = useState('');

  const grouped: Record<string, TaskCard[]> = Array.isArray(board)
    ? board.reduce<Record<string, TaskCard[]>>((acc, t) => {
        if (!acc[t.status]) acc[t.status] = [];
        acc[t.status].push(t);
        return acc;
      }, {})
    : (board ?? {});

  function applyFilters(e: React.FormEvent) {
    e.preventDefault();
    const p = new URLSearchParams();
    if (status) p.set('status', status);
    if (priority) p.set('priority', priority);
    window.location.href = `/tasks/my?${p.toString()}`;
  }

  function moveTask(taskId: string, toStatus: StatusKey) {
    if (toStatus === 'blocked') {
      setBlockedFor(taskId);
      setBlockedReason('');
      return;
    }
    router.patch(`/tasks/${taskId}/status`, { status: toStatus }, { preserveScroll: true });
  }

  function confirmBlocked(taskId: string) {
    if (!blockedReason.trim()) return;
    router.patch(`/tasks/${taskId}/status`, { status: 'blocked', blocked_reason: blockedReason }, {
      preserveScroll: true,
      onSuccess: () => {
        setBlockedFor(null);
        setBlockedReason('');
      },
    });
  }

  const isOverdue = (due: string | null, st: string) =>
    due && st !== 'done' && st !== 'cancelled' && new Date(due) < new Date(new Date().toDateString());

  return (
    <>
      <Head title="Minhas tarefas" />
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Minhas tarefas</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Arraste os cards para mudar o status (estilo Trello).</p>
        </div>
        <div className="flex items-center gap-2">
          <div className="inline-flex rounded-md border border-gray-300 dark:border-gray-700 p-0.5 text-sm">
            <button
              type="button"
              onClick={() => setView('kanban')}
              className={`rounded px-3 py-1 ${view === 'kanban' ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-300'}`}
            >
              Kanban
            </button>
            <button
              type="button"
              onClick={() => setView('list')}
              className={`rounded px-3 py-1 ${view === 'list' ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-300'}`}
            >
              Lista
            </button>
          </div>
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

      {view === 'kanban' ? (
        <div className="mt-4 flex gap-4 overflow-x-auto pb-4">
          {COLUMNS.map((col) => {
            const cards = grouped[col.key] ?? [];
            return (
              <div
                key={col.key}
                onDragOver={(e) => {
                  e.preventDefault();
                  setOverCol(col.key);
                }}
                onDragLeave={() => setOverCol(null)}
                onDrop={(e) => {
                  e.preventDefault();
                  setOverCol(null);
                  if (dragId) moveTask(dragId, col.key);
                  setDragId(null);
                }}
                className={`w-72 shrink-0 rounded-xl border-2 p-3 transition-colors ${
                  overCol === col.key ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/50'
                }`}
              >
                <div className="mb-3 flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className={`h-2.5 w-2.5 rounded-full ${col.color}`} />
                    <span className="text-sm font-semibold text-gray-900 dark:text-gray-100">{col.label}</span>
                  </div>
                  <span className="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                    {cards.length}
                  </span>
                </div>

                <div className="space-y-2">
                  {cards.length === 0 && (
                    <p className="rounded-lg border border-dashed border-gray-300 p-3 text-center text-xs text-gray-400 dark:border-gray-700">
                      Arraste tarefas para cá
                    </p>
                  )}
                  {cards.map((t) => (
                    <div
                      key={t.id}
                      draggable
                      onDragStart={(e) => {
                        setDragId(t.id);
                        e.dataTransfer.effectAllowed = 'move';
                      }}
                      onDragEnd={() => setDragId(null)}
                      className={`cursor-grab rounded-lg border bg-white p-3 shadow-sm transition hover:shadow dark:bg-gray-800 dark:border-gray-700 ${
                        dragId === t.id ? 'opacity-50' : ''
                      }`}
                    >
                      <Link href={`/tasks/${t.id}`} className="block font-medium text-blue-600 hover:underline">
                        {t.title}
                      </Link>
                      <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t.project?.title ?? '—'}</p>
                      <div className="mt-2 flex flex-wrap items-center gap-2">
                        {priorityBadge(t.priority)}
                        {t.due_at && (
                          <span className={`text-xs ${isOverdue(t.due_at, t.status) ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'}`}>
                            {new Date(t.due_at).toLocaleDateString('pt-BR')}
                            {isOverdue(t.due_at, t.status) ? ' (atrasada)' : ''}
                          </span>
                        )}
                      </div>
                      {t.blocked_reason && (
                        <p className="mt-2 rounded bg-red-50 p-2 text-xs text-red-700 dark:bg-red-900/20 dark:text-red-300">{t.blocked_reason}</p>
                      )}
                      {blockedFor === t.id && (
                        <div className="mt-2 space-y-2 rounded border border-red-200 bg-red-50 p-2 dark:border-red-900/50 dark:bg-red-900/20">
                          <Label className="text-xs">Motivo do bloqueio (obrigatório)</Label>
                          <Input value={blockedReason} onChange={(e) => setBlockedReason(e.target.value)} placeholder="Por que bloqueou?" className="text-xs" />
                          <div className="flex gap-1">
                            <Button size="sm" variant="destructive" disabled={!blockedReason.trim()} onClick={() => confirmBlocked(t.id)}>
                              Confirmar
                            </Button>
                            <Button size="sm" variant="outline" onClick={() => setBlockedFor(null)}>
                              Cancelar
                            </Button>
                          </div>
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            );
          })}
        </div>
      ) : (
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
      )}
    </>
  );
}
