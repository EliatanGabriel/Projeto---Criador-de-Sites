import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function TasksCreate({ projects, users, projectId }: { projects: { id: string; title: string }[]; users: { id: string; name: string }[]; projectId?: string }) {
  const form = useForm({
    title: '',
    description: '',
    project_id: projectId ?? '',
    assignee_id: '',
    priority: 'medium',
    status: 'todo',
    started_at: '',
    due_at: '',
    blocked_reason: '',
    notes: '',
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.post('/tasks');
  }

  return (
    <AuthenticatedLayout>
      <Head title="Nova tarefa" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">Nova tarefa</h2>
      <Card className="max-w-3xl">
        <CardHeader>
          <CardTitle>Dados da tarefa</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="title">Título *</Label>
              <Input id="title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} required />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="description">Descrição</Label>
              <textarea id="description" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm" rows={2} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="project_id">Projeto *</Label>
              <select id="project_id" value={form.data.project_id} onChange={(e) => form.setData('project_id', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm" required>
                <option value="">— Selecione —</option>
                {projects.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.title}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="assignee_id">Responsável</Label>
              <select id="assignee_id" value={form.data.assignee_id} onChange={(e) => form.setData('assignee_id', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
                <option value="">— Sem responsável —</option>
                {users.map((u) => (
                  <option key={u.id} value={u.id}>
                    {u.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="priority">Prioridade *</Label>
              <select id="priority" value={form.data.priority} onChange={(e) => form.setData('priority', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
                <option value="low">Baixa</option>
                <option value="medium">Normal</option>
                <option value="high">Alta</option>
                <option value="urgent">Urgente</option>
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="status">Status *</Label>
              <select id="status" value={form.data.status} onChange={(e) => form.setData('status', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
                <option value="todo">A fazer</option>
                <option value="in_progress">Em andamento</option>
                <option value="in_review">Em revisão</option>
                <option value="blocked">Bloqueada</option>
                <option value="done">Concluída</option>
                <option value="cancelled">Cancelada</option>
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="started_at">Início</Label>
              <Input id="started_at" type="date" value={form.data.started_at} onChange={(e) => form.setData('started_at', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="due_at">Prazo</Label>
              <Input id="due_at" type="date" value={form.data.due_at} onChange={(e) => form.setData('due_at', e.target.value)} />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="blocked_reason">Justificativa do bloqueio (se bloqueada)</Label>
              <Input id="blocked_reason" value={form.data.blocked_reason} onChange={(e) => form.setData('blocked_reason', e.target.value)} />
            </div>
            <div className="sm:col-span-2">
              <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Salvando...' : 'Criar tarefa'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
