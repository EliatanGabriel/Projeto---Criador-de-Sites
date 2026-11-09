import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Task = { title: string; description: string; priority: string; suggested_role: string; estimated_days: string; order: number; is_required: boolean };
type Phase = { name: string; description: string; order: number; tasks: Task[] };

export default function TemplatesCreate({ categories }: { categories: string[] }) {
  const [phases, setPhases] = useState<Phase[]>([{ name: 'Planejamento', description: '', order: 1, tasks: [{ title: '', description: '', priority: 'medium', suggested_role: 'owner', estimated_days: '', order: 1, is_required: true }] }]);
  const form = useForm({
    name: '',
    description: '',
    category: categories[0] ?? 'Site institucional',
    version: '1.0',
    is_active: true,
    phases: [] as any[],
  });

  function updatePhase(i: number, field: string, value: any) {
    setPhases((ps) => ps.map((p, idx) => (idx === i ? { ...p, [field]: value } : p)));
  }
  function updateTask(pi: number, ti: number, field: string, value: any) {
    setPhases((ps) => ps.map((p, idx) => (idx === pi ? { ...p, tasks: p.tasks.map((t, tIdx) => (tIdx === ti ? { ...t, [field]: value } : t)) } : p)));
  }
  function addPhase() {
    setPhases((ps) => [...ps, { name: `Etapa ${ps.length + 1}`, description: '', order: ps.length + 1, tasks: [{ title: '', description: '', priority: 'medium', suggested_role: 'developer', estimated_days: '', order: 1, is_required: true }] }]);
  }
  function addTask(pi: number) {
    setPhases((ps) => ps.map((p, idx) => (idx === pi ? { ...p, tasks: [...p.tasks, { title: '', description: '', priority: 'medium', suggested_role: 'developer', estimated_days: '', order: p.tasks.length + 1, is_required: true }] } : p)));
  }

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.transform((data) => ({
      ...data,
      phases: phases.map((p, pi) => ({
        name: p.name,
        description: p.description,
        order: pi + 1,
        tasks: p.tasks.map((t, ti) => ({
          title: t.title,
          description: t.description,
          priority: t.priority,
          suggested_role: t.suggested_role || null,
          estimated_days: t.estimated_days ? Number(t.estimated_days) : null,
          order: ti + 1,
          is_required: t.is_required,
        })),
      })),
    }));
    form.post('/templates');
  }

  return (
    <>
      <Head title="Novo template" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">Novo template</h2>
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
              <textarea id="description" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm" rows={2} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="category">Categoria *</Label>
              <select id="category" value={form.data.category} onChange={(e) => form.setData('category', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
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
            <label className="flex items-center gap-2 text-sm sm:col-span-2">
              <input type="checkbox" checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} />
              Template ativo
            </label>
          </CardContent>
        </Card>

        {phases.map((p, pi) => (
          <Card key={pi} className="max-w-3xl">
            <CardHeader className="flex flex-row items-center justify-between">
              <CardTitle>Etapa {pi + 1}</CardTitle>
              <Button type="button" variant="outline" size="sm" onClick={() => setPhases((ps) => ps.filter((_, i) => i !== pi))}>
                Remover etapa
              </Button>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label>Nome da etapa *</Label>
                  <Input value={p.name} onChange={(e) => updatePhase(pi, 'name', e.target.value)} required />
                </div>
                <div className="space-y-2">
                  <Label>Descrição</Label>
                  <Input value={p.description} onChange={(e) => updatePhase(pi, 'description', e.target.value)} />
                </div>
              </div>

              {p.tasks.map((t, ti) => (
                <div key={ti} className="rounded-md border p-3">
                  <div className="mb-2 flex items-center justify-between">
                    <span className="text-sm font-medium">Tarefa {ti + 1}</span>
                    <Button type="button" variant="ghost" size="sm" onClick={() => setPhases((ps) => ps.map((pp, idx) => (idx === pi ? { ...pp, tasks: pp.tasks.filter((_, j) => j !== ti) } : pp)))}>
                      Remover
                    </Button>
                  </div>
                  <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div className="space-y-2 sm:col-span-2">
                      <Label>Título *</Label>
                      <Input value={t.title} onChange={(e) => updateTask(pi, ti, 'title', e.target.value)} required />
                    </div>
                    <div className="space-y-2 sm:col-span-2">
                      <Label>Descrição</Label>
                      <textarea value={t.description} onChange={(e) => updateTask(pi, ti, 'description', e.target.value)} className="w-full rounded-md border p-2 text-sm" rows={2} />
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
                    <div className="space-y-2">
                      <Label>Prazo estimado (dias)</Label>
                      <Input type="number" min={0} value={t.estimated_days} onChange={(e) => updateTask(pi, ti, 'estimated_days', e.target.value)} />
                    </div>
                    <label className="flex items-center gap-2 text-sm">
                      <input type="checkbox" checked={t.is_required} onChange={(e) => updateTask(pi, ti, 'is_required', e.target.checked)} />
                      Obrigatória
                    </label>
                  </div>
                </div>
              ))}

              <Button type="button" variant="outline" size="sm" onClick={() => addTask(pi)}>
                + Adicionar tarefa
              </Button>
            </CardContent>
          </Card>
        ))}

        <div className="flex gap-3">
          <Button type="button" variant="secondary" onClick={addPhase}>
            + Adicionar etapa
          </Button>
          <Button type="submit" disabled={form.processing}>
            {form.processing ? 'Salvando...' : 'Salvar template'}
          </Button>
        </div>
      </form>
    </>
  );
}
