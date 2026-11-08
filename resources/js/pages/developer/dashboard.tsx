import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface TaskItem {
  id: string;
  title: string;
  status: string;
  priority: string;
  due_at: string | null;
  blocked_reason: string | null;
  project_id: string;
  project?: { title: string } | null;
}

interface ProjectItem {
  id: string;
  title: string;
  status: string;
  priority: string;
  client?: { name: string } | null;
}

export default function DeveloperDashboard({
  stats,
  myTasks,
  blockedTasks,
  myProjects,
}: {
  stats: { todo: number; in_progress: number; in_review: number; blocked: number; overdue: number; done: number; projects: number; active_projects: number };
  myTasks: TaskItem[];
  blockedTasks: TaskItem[];
  myProjects: ProjectItem[];
}) {
  const date = (v: string | null) => (v ? new Date(v).toLocaleDateString('pt-BR') : '—');

  return (
    <AuthenticatedLayout>
      <Head title="Painel Developer" />
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Painel Developer</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Suas tarefas, projetos atribuídos, briefings e bloqueios.</p>
        </div>
        <Link href="/tasks/my">
          <Button>Minhas tarefas</Button>
        </Link>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {[
          ['A fazer', stats.todo, '/tasks/my?status=todo'],
          ['Em andamento', stats.in_progress, '/tasks/my?status=in_progress'],
          ['Em revisão', stats.in_review, '/tasks/my?status=in_review'],
          ['Bloqueadas', stats.blocked, '/tasks/my?status=blocked'],
          ['Atrasadas', stats.overdue, '/tasks/my'],
          ['Concluídas', stats.done, '/tasks/my?status=done'],
          ['Projetos atribuídos', stats.projects, '/projects'],
          ['Projetos ativos', stats.active_projects, '/projects'],
        ].map(([label, value, href]) => (
          <Card key={label as string}>
            <CardHeader>
              <CardDescription>{label}</CardDescription>
              <CardTitle className="text-3xl">{value}</CardTitle>
            </CardHeader>
            <CardContent>
              <Link href={href as string} className="text-sm text-blue-600 hover:underline">
                Abrir →
              </Link>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="mt-6 grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Minhas tarefas recentes</CardTitle>
            <CardDescription>Atribuídas a você</CardDescription>
          </CardHeader>
          <CardContent>
            {myTasks.length === 0 ? (
              <p className="text-sm text-gray-500 dark:text-gray-400">Nenhuma tarefa atribuída.</p>
            ) : (
              <ul className="space-y-2 text-sm">
                {myTasks.map((t) => (
                  <li key={t.id} className="flex items-center justify-between border-b pb-2 last:border-0">
                    <div>
                      <Link href={`/tasks/${t.id}`} className="font-medium text-blue-600 hover:underline">
                        {t.title}
                      </Link>
                      <p className="text-xs text-gray-500 dark:text-gray-400">
                        {t.project?.title ?? ''} • {t.status} • {t.priority} • prazo {date(t.due_at)}
                      </p>
                    </div>
                  </li>
                ))}
              </ul>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Tarefas bloqueadas</CardTitle>
            <CardDescription>Com motivo de bloqueio registrado</CardDescription>
          </CardHeader>
          <CardContent>
            {blockedTasks.length === 0 ? (
              <p className="text-sm text-gray-500 dark:text-gray-400">Nenhuma tarefa bloqueada.</p>
            ) : (
              <ul className="space-y-2 text-sm">
                {blockedTasks.map((t) => (
                  <li key={t.id} className="border-b pb-2 last:border-0">
                    <Link href={`/tasks/${t.id}`} className="font-medium text-blue-600 hover:underline">
                      {t.title}
                    </Link>
                    <p className="text-xs text-red-600 dark:text-red-400">{t.blocked_reason ?? 'Bloqueada sem motivo'}</p>
                  </li>
                ))}
              </ul>
            )}
          </CardContent>
        </Card>
      </div>

      <Card className="mt-6">
        <CardHeader>
          <CardTitle>Projetos atribuídos</CardTitle>
          <CardDescription>Abra o projeto para ver briefing, anexos, tarefas e atualizar status/bloqueio</CardDescription>
        </CardHeader>
        <CardContent>
          {myProjects.length === 0 ? (
            <p className="text-sm text-gray-500 dark:text-gray-400">Nenhum projeto atribuído.</p>
          ) : (
            <ul className="space-y-2 text-sm">
              {myProjects.map((p) => (
                <li key={p.id} className="flex items-center justify-between border-b pb-2 last:border-0">
                  <div>
                    <Link href={`/projects/${p.id}`} className="font-medium text-blue-600 hover:underline">
                      {p.title}
                    </Link>
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                      {p.client?.name ?? 'Sem cliente'} • {p.status} • {p.priority}
                    </p>
                  </div>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>

      <Card className="mt-6">
        <CardHeader>
          <CardTitle>Atalhos</CardTitle>
        </CardHeader>
        <CardContent className="flex flex-wrap gap-2">
          {[
            ['Projetos', '/projects'],
            ['Minhas tarefas', '/tasks/my'],
            ['Todas as tarefas', '/tasks'],
            ['Templates', '/templates'],
            ['Sites', '/sites'],
            ['Alertas', '/alerts'],
          ].map(([label, href]) => (
            <Link key={href} href={href}>
              <Button variant="outline">{label}</Button>
            </Link>
          ))}
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
