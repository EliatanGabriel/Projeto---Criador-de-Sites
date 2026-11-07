import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function ProjectsEdit({ project, users }: { project: any; users: { id: string; name: string }[] }) {
  const form = useForm({
    title: project.title ?? '',
    description: project.description ?? '',
    scope: project.scope ?? '',
    technical_owner_id: project.technical_owner_id ?? '',
    started_at: (project.started_at ?? '').slice(0, 10),
    planned_deadline: (project.planned_deadline ?? '').slice(0, 10),
    priority: project.priority ?? 'medium',
    contracted_value: project.contracted_value?.toString() ?? '',
    internal_notes: project.internal_notes ?? '',
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.put(`/projects/${project.id}`);
  }

  return (
    <AuthenticatedLayout>
      <Head title="Editar projeto" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900">Editar projeto</h2>
      <Card className="max-w-3xl">
        <CardHeader>
          <CardTitle>{project.title}</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="title">Título *</Label>
              <Input id="title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} required />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="description">Descrição</Label>
              <textarea id="description" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" rows={2} />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="scope">Escopo</Label>
              <textarea id="scope" value={form.data.scope} onChange={(e) => form.setData('scope', e.target.value)} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" rows={2} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="technical_owner_id">Responsável técnico</Label>
              <select id="technical_owner_id" value={form.data.technical_owner_id} onChange={(e) => form.setData('technical_owner_id', e.target.value)} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">— Selecione —</option>
                {users.map((u) => (
                  <option key={u.id} value={u.id}>
                    {u.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="priority">Prioridade *</Label>
              <select id="priority" value={form.data.priority} onChange={(e) => form.setData('priority', e.target.value)} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="low">Baixa</option>
                <option value="medium">Normal</option>
                <option value="high">Alta</option>
                <option value="urgent">Urgente</option>
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="started_at">Início</Label>
              <Input id="started_at" type="date" value={form.data.started_at} onChange={(e) => form.setData('started_at', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="planned_deadline">Prazo planejado</Label>
              <Input id="planned_deadline" type="date" value={form.data.planned_deadline} onChange={(e) => form.setData('planned_deadline', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="contracted_value">Valor contratado (BRL)</Label>
              <Input id="contracted_value" type="number" step="0.01" value={form.data.contracted_value} onChange={(e) => form.setData('contracted_value', e.target.value)} />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="internal_notes">Observações internas</Label>
              <textarea id="internal_notes" value={form.data.internal_notes} onChange={(e) => form.setData('internal_notes', e.target.value)} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" rows={2} />
            </div>
            <div className="sm:col-span-2">
              <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Salvando...' : 'Salvar'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
