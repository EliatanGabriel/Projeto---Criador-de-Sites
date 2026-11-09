import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface DemandItem {
  id: string;
  title: string;
  status: string;
  priority: string;
  submitted_at: string | null;
  created_at: string;
  desired_deadline?: string | null;
  client?: { name: string } | null;
  commercial_owner?: { name: string } | null;
  waiting_days?: number;
  can_start_review?: boolean;
  can_approve?: boolean;
  can_reject?: boolean;
  can_request_information?: boolean;
}

interface TaskItem {
  id: string;
  title: string;
  status: string;
  priority: string;
  due_at: string | null;
  project?: { title: string } | null;
  assignee?: { name: string } | null;
}

interface ProjectItem {
  id: string;
  title: string;
  status: string;
  priority: string;
  client?: { name: string } | null;
  technical_owner?: { name: string } | null;
}

const statusLabels: Record<string, string> = {
  submitted: 'Enviada',
  under_review: 'Em análise',
  needs_information: 'Aguardando info',
  draft: 'Rascunho',
  approved: 'Aprovada',
  rejected: 'Recusada',
  cancelled: 'Cancelada',
  in_progress: 'Em andamento',
  blocked: 'Bloqueado',
  todo: 'A fazer',
  done: 'Concluída',
};

function StatusBadge({ status }: { status: string }) {
  const styles: Record<string, string> = {
    submitted: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    under_review: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    needs_information: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
    approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    draft: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    cancelled: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
    in_progress: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    blocked: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    todo: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
    done: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    planning: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    in_review: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    completed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
  };
  return (
    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${styles[status] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'}`}>
      {statusLabels[status] ?? status}
    </span>
  );
}

function PriorityText({ priority }: { priority: string }) {
  const colors: Record<string, string> = {
    urgent: 'text-red-600 font-semibold dark:text-red-400',
    high: 'text-orange-600 dark:text-orange-400',
    medium: 'text-gray-600 dark:text-gray-300',
    low: 'text-gray-400 dark:text-gray-500',
  };
  return <span className={colors[priority] ?? ''}>{priority}</span>;
}

export default function AdminDashboard({
  stats,
  pendingDemands,
  recentDemands,
  overdueTasks,
  activeProjects,
  financeSummary,
}: {
  stats: { active_clients: number; open_opportunities: number; pending_demands: number; draft_demands: number; approved_demands: number; open_estimated_total: number; active_projects: number; overdue_tasks: number; open_alerts: number };
  pendingDemands: DemandItem[];
  recentDemands: DemandItem[];
  overdueTasks: TaskItem[];
  activeProjects: ProjectItem[];
  financeSummary: { receita_pendente: number; receita_vencida: number; receita_recebida_mes: number; despesa_paga_mes: number };
}) {
  const [actionFor, setActionFor] = useState<string | null>(null);
  const [reason, setReason] = useState('');
  const form = useForm({});
  const brl = (v: number) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const date = (v: string | null) => (v ? new Date(v).toLocaleString('pt-BR') : '—');

  const postAction = (demandId: string, endpoint: string, payload: Record<string, string>) => {
    form.transform(() => payload);
    form.post(`/demands/${demandId}/${endpoint}`, { preserveScroll: true, onSuccess: () => { setReason(''); setActionFor(null); } });
  };

  return (
    <>
      <Head title="Painel Admin" />
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Painel Admin</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Visão geral — demandas, execução e financeiro.</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <Link href="/commercial">
            <Button variant="outline">Painel Comercial</Button>
          </Link>
          <Link href="/developer">
            <Button variant="outline">Painel Developer</Button>
          </Link>
          <Link href="/demands">
            <Button>Ver demandas</Button>
          </Link>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {([
          ['Demandas aguardando análise', stats.pending_demands, '/demands?status=submitted'],
          ['Rascunhos', stats.draft_demands, '/demands?status=draft'],
          ['Aprovadas', stats.approved_demands, '/demands?status=approved'],
          ['Clientes ativos', stats.active_clients, '/clients'],
          ['Oportunidades abertas', stats.open_opportunities, '/opportunities'],
          ['Pipeline estimado', brl(stats.open_estimated_total), '/opportunities'],
          ['Projetos ativos', stats.active_projects, '/projects'],
          ['Tarefas atrasadas', stats.overdue_tasks, '/tasks'],
          ['Alertas abertos', stats.open_alerts, '/alerts'],
        ] as const).map(([label, value, href]) => (
          <Card key={label}>
            <CardHeader>
              <CardDescription>{label}</CardDescription>
              <CardTitle className="text-3xl">{value}</CardTitle>
            </CardHeader>
            <CardContent>
              <Link href={href} className="text-sm text-blue-600 hover:underline">
                Abrir →
              </Link>
            </CardContent>
          </Card>
        ))}
      </div>

      <Card className="mt-6">
        <CardHeader>
          <CardTitle>Aguardando sua análise</CardTitle>
          <CardDescription>Demandas enviadas pelo comercial — ações rápidas no painel</CardDescription>
        </CardHeader>
        <CardContent>
          {pendingDemands.length === 0 ? (
            <p className="text-sm text-gray-500 dark:text-gray-400">Nenhuma demanda aguardando análise.</p>
          ) : (
            <ul className="space-y-3 text-sm">
              {pendingDemands.map((d) => (
                <li key={d.id} className="border-b pb-3 last:border-0">
                  <div className="flex flex-wrap items-start justify-between gap-2">
                    <div>
                      <Link href={`/demands/${d.id}`} className="font-medium text-blue-600 hover:underline">
                        {d.title}
                      </Link>
                      <p className="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span>{d.client?.name ?? 'Sem cliente'} • {d.commercial_owner?.name ?? ''}</span>
                        <StatusBadge status={d.status} />
                        <PriorityText priority={d.priority} />
                        <span>{date(d.submitted_at)}</span>
                        {(d.waiting_days ?? 0) > 0 && (
                          <span className={(d.waiting_days ?? 0) >= 7 ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'}>
                            aguardando há {d.waiting_days}d
                          </span>
                        )}
                      </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-1">
                      {d.can_start_review && (
                        <Button size="sm" variant="secondary" disabled={form.processing} onClick={() => postAction(d.id, 'start-review', {})}>
                          Assumir análise
                        </Button>
                      )}
                      {d.can_approve && (
                        <Button size="sm" disabled={form.processing} onClick={() => postAction(d.id, 'approve', { notes: '' })}>
                          Aprovar
                        </Button>
                      )}
                      {d.can_request_information && (
                        <Button size="sm" variant="outline" onClick={() => { setActionFor(actionFor === `info-${d.id}` ? null : `info-${d.id}`); setReason(''); }}>
                          Solicitar info
                        </Button>
                      )}
                      {d.can_reject && (
                        <Button size="sm" variant="destructive" onClick={() => { setActionFor(actionFor === `reject-${d.id}` ? null : `reject-${d.id}`); setReason(''); }}>
                          Recusar
                        </Button>
                      )}
                    </div>
                  </div>
                  {actionFor === `info-${d.id}` && d.can_request_information && (
                    <div className="mt-2 flex flex-wrap items-end gap-2 rounded-lg bg-gray-50 p-3 dark:bg-gray-800/50">
                      <div className="min-w-48 flex-1 space-y-1">
                        <Label>Motivo da complementação (obrigatório)</Label>
                        <Input value={reason} onChange={(e) => setReason(e.target.value)} placeholder="O que falta?" />
                      </div>
                      <Button size="sm" variant="secondary" disabled={form.processing || !reason.trim()} onClick={() => postAction(d.id, 'request-information', { reason })}>
                        Enviar solicitação
                      </Button>
                    </div>
                  )}
                  {actionFor === `reject-${d.id}` && d.can_reject && (
                    <div className="mt-2 flex flex-wrap items-end gap-2 rounded-lg bg-red-50 p-3 dark:bg-red-900/20">
                      <div className="min-w-48 flex-1 space-y-1">
                        <Label>Motivo da recusa (obrigatório)</Label>
                        <Input value={reason} onChange={(e) => setReason(e.target.value)} placeholder="Justificativa" />
                      </div>
                      <Button size="sm" variant="destructive" disabled={form.processing || !reason.trim()} onClick={() => postAction(d.id, 'reject', { reason })}>
                        Confirmar recusa
                      </Button>
                    </div>
                  )}
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>

      <div className="mt-6 grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Projetos ativos</CardTitle>
            <CardDescription>Em andamento, revisão ou bloqueados</CardDescription>
          </CardHeader>
          <CardContent>
            {activeProjects.length === 0 ? (
              <p className="text-sm text-gray-500 dark:text-gray-400">Nenhum projeto ativo.</p>
            ) : (
              <ul className="space-y-2 text-sm">
                {activeProjects.map((p) => (
                  <li key={p.id} className="flex items-center justify-between border-b pb-2 last:border-0">
                    <div>
                      <Link href={`/projects/${p.id}`} className="font-medium text-blue-600 hover:underline">
                        {p.title}
                      </Link>
                      <p className="mt-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span>{p.client?.name ?? 'Sem cliente'} • {p.technical_owner?.name ?? ''}</span>
                        <StatusBadge status={p.status} />
                      </p>
                    </div>
                  </li>
                ))}
              </ul>
            )}
            <Link href="/projects" className="mt-3 inline-block text-sm text-blue-600 hover:underline">
              Ver todos os projetos →
            </Link>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Tarefas atrasadas</CardTitle>
            <CardDescription>Vencidas e não concluídas</CardDescription>
          </CardHeader>
          <CardContent>
            {overdueTasks.length === 0 ? (
              <p className="text-sm text-gray-500 dark:text-gray-400">Nenhuma tarefa atrasada.</p>
            ) : (
              <ul className="space-y-2 text-sm">
                {overdueTasks.map((t) => (
                  <li key={t.id} className="flex items-center justify-between border-b pb-2 last:border-0">
                    <div>
                      <Link href={`/tasks/${t.id}`} className="font-medium text-blue-600 hover:underline">
                        {t.title}
                      </Link>
                      <p className="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span>{t.project?.title ?? ''} • {t.assignee?.name ?? ''}</span>
                        <StatusBadge status={t.status} />
                        <span className="text-red-600 dark:text-red-400">venc. {t.due_at ? new Date(t.due_at).toLocaleDateString('pt-BR') : '—'}</span>
                      </p>
                    </div>
                  </li>
                ))}
              </ul>
            )}
            <Link href="/tasks" className="mt-3 inline-block text-sm text-blue-600 hover:underline">
              Ver todas as tarefas →
            </Link>
          </CardContent>
        </Card>
      </div>

      <Card className="mt-6">
        <CardHeader>
          <CardTitle>Financeiro resumido</CardTitle>
          <CardDescription>Mês atual — contratado vs recebido (veja o relatório para detalhes)</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-sm">
            <div>
              <p className="text-gray-500 dark:text-gray-400">Receita a receber</p>
              <p className="text-xl font-bold">{brl(financeSummary.receita_pendente)}</p>
            </div>
            <div>
              <p className="text-gray-500 dark:text-gray-400">Receita vencida</p>
              <p className={`text-xl font-bold ${financeSummary.receita_vencida > 0 ? 'text-red-600 dark:text-red-400' : ''}`}>{brl(financeSummary.receita_vencida)}</p>
            </div>
            <div>
              <p className="text-gray-500 dark:text-gray-400">Recebido no mês</p>
              <p className="text-xl font-bold text-green-600 dark:text-green-400">{brl(financeSummary.receita_recebida_mes)}</p>
            </div>
            <div>
              <p className="text-gray-500 dark:text-gray-400">Despesa paga no mês</p>
              <p className="text-xl font-bold">{brl(financeSummary.despesa_paga_mes)}</p>
            </div>
          </div>
          <div className="mt-3 flex flex-wrap gap-3 text-sm">
            <Link href="/finance/report" className="text-blue-600 hover:underline">
              Relatório financeiro →
            </Link>
            <Link href="/finance" className="text-blue-600 hover:underline">
              Ver lançamentos →
            </Link>
          </div>
        </CardContent>
      </Card>

      <Card className="mt-6">
        <CardHeader>
          <CardTitle>Demandas recentes</CardTitle>
        </CardHeader>
        <CardContent>
          {recentDemands.length === 0 ? (
            <p className="text-sm text-gray-500 dark:text-gray-400">Nenhuma demanda ainda.</p>
          ) : (
            <ul className="space-y-2 text-sm">
              {recentDemands.map((d) => (
                <li key={d.id} className="flex items-center justify-between border-b pb-2 last:border-0">
                  <Link href={`/demands/${d.id}`} className="font-medium text-blue-600 hover:underline">
                    {d.title}
                  </Link>
                  <span className="flex items-center gap-2">
                    <StatusBadge status={d.status} />
                    <span className="text-xs text-gray-500 dark:text-gray-400">{d.status}</span>
                  </span>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>
    </>
  );
}
