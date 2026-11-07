import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function TemplatesEdit({ template, categories }: { template: any; categories: string[] }) {
  const [phases, setPhases] = useState<any[]>(() =>
    (template.phases ?? []).map((p: any) => ({
      name: p.name,
      description: p.description ?? '',
      order: p.order,
      tasks: (p.tasks ?? []).map((t: any) => ({ title: t.title, description: t.description ?? '', priority: t.priority, suggested_role: t.suggested_role ?? 'developer', estimated_days: t.estimated_days?.toString() ?? '', order: t.order, is_required: t.is_required })),
    }))
  );
  const form = useForm({
    name: template.name ?? '',
    description: template.description ?? '',
    category: template.category ?? categories[0] ?? 'Site institucional',
    version: template.version ?? '1.0',
    is_active: !!template.is_active,
    phases: [] as any[],
  });

  function updatePhase(i: number, field: string, value: any) {
    setPhases((ps: any[]) => ps.map((p: any, idx: number) => (idx === i ? { ...p, [field]: value } : p)));
  }
  function updateTask(pi: number, ti: number, field: string, value: any) {
    setPhases((ps: any[]) => ps.map((p: any, idx: number) => (idx === pi ? { ...p, tasks: p.tasks.map((t: any, j: number) => (j === ti ? { ...t, [field]: value } : t)) } : p)));
  }

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.transform((data) => ({
      ...data,
      phases: phases.map((p: any, pi: number) => ({
        name: p.name,
        description: p.description,
        order: pi + 1,
        tasks: p.tasks.map((t: any, ti: number) => ({ title: t.title, description: t.description, priority: t.priority, suggested_role: t.suggested_role || null, estimated_days: t.estimated_days ? Number(t.estimated_days) : null, order: ti + 1, is_required: t.is_required })),
      })),
    }));
    form.put(`/templates/${template.id}`);
  }

  return (
    <AuthenticatedLayout>
      <Head title="Editar template" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900">Editar template</h2>
      <form onSubmit={submit} className="space-y-6">
        <Card className="max-w-3xl">
          <CardHeader>
            <CardTitle>Informações básicas</CardTitle>
          </CardHeader>
          <CardContent className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="name">Nome *</Label>
              <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="description">Descrição</Label>
              <textarea id="description" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm" rows={2} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="category">Categoria *</Label>
              <select id="category" value={form.data.category} onChange={(e) => form.setData('category', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                {categories.map((c) => (
                  <option key={c} value={c}>
                    {c}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="version">Versão</Label>
              <Input id="version" value={form.data.version} onChange={(e) => form.setData('version', e.target.value)} />
            </div>
          </CardContent>
        </Card>

        {phases.map((p, pi) => (
          <Card key={pi} className="max-w-3xl">
            <CardHeader>
              <CardTitle>Etapa {pi + 1}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>Nome *</Label>
                  <Input value={p.name} onChange={(e) => updatePhase(pi, 'name', e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label>Descrição</Label>
                  <Input value={p.description} onChange={(e) => updatePhase(pi, 'description', e.target.value)} />
                </div>
              </div>
              {(p.tasks ?? []).map((t: any, ti: number) => (
                <div key={ti} className="rounded-md border p-3">
                  <p className="mb-2 text-sm font-medium">Tarefa {ti + 1}</p>
                  <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div className="space-y-2 sm:col-span-2">
                      <Label>Título *</Label>
                      <Input value={t.title} onChange={(e) => updateTask(pi, ti, 'title', e.target.value)} />
                    </div>
                    <div className="space-y-2">
                      <Label>Prioridade</Label>
                      <select value={t.priority} onChange={(e) => updateTask(pi, ti, 'priority', e.target.value)} className="w-full rounded-md border p-2 text-sm">
                        <option value="low">Baixa</option>
                        <option value="medium">Normal</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                      </select>
                    </div>
                    <div className="space-y-2">
                      <Label>Sugerido</Label>
                      <select value={t.suggested_role} onChange={(e) => updateTask(pi, ti, 'suggested_role', e.target.value)} className="w-full rounded-md border p-2 text-sm">
                        <option value="owner">OWNER</option>
                        <option value="developer">DEVELOPER</option>
                        <option value="">Sem sugestão</option>
                      </select>
                    </div>
                  </div>
                </div>
              ))}
            </CardContent>
          </Card>
        ))}

        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'Salvando...' : 'Salvar template'}
        </Button>
      </form>
    </AuthenticatedLayout>
  );
}
