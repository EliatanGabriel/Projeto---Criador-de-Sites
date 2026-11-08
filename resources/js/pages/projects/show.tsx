import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const P_STATUS: Record<string, string> = { planning: 'Planejamento', in_progress: 'Em andamento', in_review: 'Em revisão', blocked: 'Bloqueado', completed: 'Concluído', cancelled: 'Cancelado' };
const T_STATUS: Record<string, string> = { todo: 'A fazer', in_progress: 'Em andamento', in_review: 'Em revisão', blocked: 'Bloqueada', done: 'Concluída', cancelled: 'Cancelada' };

export default function ProjectsShow({ project, progress, canManage }: { project: any; progress: any; canManage: boolean }) {
  const [blockedReason, setBlockedReason] = useState('');
  const statusForm = useForm({ status: project.status, blocked_reason: '' });

  function setStatus(s: string) {
    statusForm.transform((d) => ({ status: s, blocked_reason: blockedReason }));
    statusForm.patch(`/projects/${project.id}/status`);
  }

  const timeline = [...(project.histories ?? [])].reverse();

  return (
    <AuthenticatedLayout>
      <Head title={project.title} />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">{project.title}</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {project.client?.name ?? '—'} • {P_STATUS[project.status] ?? project.status} • Progresso {progress.percentage}% ({progress.done}/{progress.total})
          </p>
        </div>
        {canManage && (
          <Link href={`/projects/${project.id}/edit`}>
            <Button>Editar planejamento</Button>
          </Link>
        )}
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Informações gerais</CardTitle>
            <CardDescription>
              Demanda de origem:{' '}
              <Link href={`/demands/${project.demand_id}`} className="text-blue-600 hover:underline">
                {project.demand?.title ?? project.demand_id}
              </Link>
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            <p><span className="font-medium">Responsável técnico:</span> {project.technical_owner?.name ?? '—'}</p>
            <p><span className="font-medium">Início:</span> {project.started_at ? new Date(project.started_at).toLocaleDateString('pt-BR') : '—'}</p>
            <p><span className="font-medium">Prazo planejado:</span> {project.planned_deadline ? new Date(project.planned_deadline).toLocaleDateString('pt-BR') : '—'}</p>
            <p><span className="font-medium">Concluído em:</span> {project.completed_at ? new Date(project.completed_at).toLocaleDateString('pt-BR') : '—'}</p>
            <p><span className="font-medium">Valor contratado:</span> {project.contracted_value ? Number(project.contracted_value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '—'}</p>
            <p><span className="font-medium">Prioridade:</span> {project.priority}</p>
            <p><span className="font-medium">Descrição:</span> {project.description ?? '—'}</p>
            <p><span className="font-medium">Escopo:</span> {project.scope ?? '—'}</p>
            {project.blocked_reason && <p><span className="font-medium">Bloqueio:</span> {project.blocked_reason}</p>}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Progresso das tarefas</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            <p>Concluídas: <b>{progress.done}</b> / {progress.total} ({progress.percentage}%)</p>
            <div className="h-2 w-full rounded bg-gray-200 dark:bg-gray-700">
              <div className="h-2 rounded bg-emerald-500" style={{ width: `${progress.percentage}%` }} />
            </div>
            <p>A fazer: {progress.todo} • Em andamento: {progress.in_progress} • Em revisão: {progress.in_review} • Bloqueadas: {progress.blocked}</p>
            <div className="flex flex-wrap items-center gap-2 pt-2">
              <Link href={`/tasks/create?project_id=${project.id}`}>
                <Button size="sm">Nova tarefa</Button>
              </Link>
              <Link href={`/tasks?project_id=${project.id}`} className="text-sm text-blue-600 hover:underline">
                Ver todas as tarefas do projeto
              </Link>
            </div>
            {canManage && (
              <div className="space-y-2 border-t pt-3">
                <Label htmlFor="blocked">Bloquear (justificativa)</Label>
                <Input id="blocked" value={blockedReason} onChange={(e) => setBlockedReason(e.target.value)} placeholder="Motivo do bloqueio" />
                <div className="flex flex-wrap gap-2">
                  {['planning', 'in_progress', 'in_review', 'blocked', 'completed', 'cancelled'].map((s) => (
                    <button key={s} onClick={() => setStatus(s)} disabled={statusForm.processing || project.status === s} className={`rounded-full px-2.5 py-1 text-xs ${project.status === s ? 'bg-gray-900 text-white' : 'bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:bg-gray-700'}`}>
                      {P_STATUS[s] ?? s}
                    </button>
                  ))}
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Tarefas</CardTitle>
          </CardHeader>
          <CardContent>
            {(project.tasks?.length ?? 0) === 0 ? (
              <p className="py-6 text-center text-sm text-gray-500 dark:text-gray-400">Nenhuma tarefa criada.</p>
            ) : (
              (() => {
                const grouped: Record<string, any[]> = {};
                (project.tasks ?? []).forEach((t: any) => {
                  const phase = t.phase_name ?? 'Sem etapa';
                  if (!grouped[phase]) grouped[phase] = [];
                  grouped[phase].push(t);
                });
                const ordered = Object.entries(grouped).sort((a, b) => {
                  const pa = a[1][0]?.phase_order ?? 999;
                  const pb = b[1][0]?.phase_order ?? 999;
                  return pa - pb;
                });
                return ordered.map(([phase, tasks]) => (
                  <div key={phase} className="mb-4">
                    <p className="mb-2 font-medium">{phase}</p>
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="border-b text-left text-gray-600 dark:text-gray-400">
                          <th className="pb-2">Título</th>
                          <th className="pb-2">Responsável</th>
                          <th className="pb-2">Status</th>
                          <th className="pb-2">Prioridade</th>
                          <th className="pb-2">Prazo</th>
                          <th className="pb-2 text-right">Ações</th>
                        </tr>
                      </thead>
                      <tbody>
                        {tasks.map((t: any) => (
                          <tr key={t.id} className="border-b last:border-0">
                            <td className="py-2 font-medium">{t.title}</td>
                            <td className="py-2">{t.assignee?.name ?? '—'}</td>
                            <td className="py-2">{T_STATUS[t.status] ?? t.status}</td>
                            <td className="py-2">{t.priority}</td>
                            <td className="py-2 text-gray-500 dark:text-gray-400">{t.due_at ? new Date(t.due_at).toLocaleDateString('pt-BR') : '—'}</td>
                            <td className="py-2 text-right">
                              <Link href={`/tasks/${t.id}`} className="text-blue-600 hover:underline">
                                Ver
                              </Link>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                ));
              })()
            )}
          </CardContent>
        </Card>

        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Histórico</CardTitle>
          </CardHeader>
          <CardContent>
            {timeline.length === 0 ? (
              <p className="text-sm text-gray-500 dark:text-gray-400">Sem histórico.</p>
            ) : (
              <ul className="space-y-2 text-sm">
                {timeline.map((h: any) => (
                  <li key={h.id} className="border-b pb-2">
                    <p className="font-medium">{h.event}</p>
                    {h.description && <p className="text-gray-600 dark:text-gray-400">{h.description}</p>}
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                      {h.user?.name ?? 'Sistema'} • {new Date(h.created_at).toLocaleString('pt-BR')}
                    </p>
                  </li>
                ))}
              </ul>
            )}
          </CardContent>
        </Card>
      </div>
    </AuthenticatedLayout>
  );
}
