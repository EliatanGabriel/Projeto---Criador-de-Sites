import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function ProjectsCreate({ demands, templates, users }: { demands: { id: string; title: string; client_id: string }[]; templates: any[]; users: { id: string; name: string }[] }) {
  const [templateId, setTemplateId] = useState('');
  const [assignments, setAssignments] = useState<Record<string, string>>({});
  const [excluded, setExcluded] = useState<Record<string, boolean>>({});
  const form = useForm({
    demand_id: demands[0]?.id ?? '',
    template_id: '',
    assignments: {} as Record<string, string>,
    excluded_task_ids: [] as string[],
  });

  const selectedTemplate = templates.find((t: any) => t.id === templateId);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.transform((d) => ({
      ...d,
      template_id: templateId || null,
      assignments,
      excluded_task_ids: Object.entries(excluded).filter(([, v]) => v).map(([k]) => k),
    }));
    form.post('/projects');
  }

  return (
    <AuthenticatedLayout>
      <Head title="Converter demanda" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">Converter demanda em projeto</h2>
      {demands.length === 0 ? (
        <Card className="max-w-2xl">
          <CardContent className="pt-6">
            <p className="text-sm text-gray-500 dark:text-gray-400">Nenhuma demanda aprovada sem projeto.</p>
          </CardContent>
        </Card>
      ) : (
        <form onSubmit={submit} className="space-y-6">
          <Card className="max-w-3xl">
            <CardHeader>
              <CardTitle>Conversão</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="demand_id">Demanda aprovada *</Label>
                <select id="demand_id" value={form.data.demand_id} onChange={(e) => form.setData('demand_id', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm" required>
                  {demands.map((d) => (
                    <option key={d.id} value={d.id}>
                      {d.title}
                    </option>
                  ))}
                </select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="template_id">Template (opcional)</Label>
                <select id="template_id" value={templateId} onChange={(e) => setTemplateId(e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                  <option value="">— Sem template —</option>
                  {templates.map((t: any) => (
                    <option key={t.id} value={t.id}>
                      {t.name} (v{t.version}) — {t.category}
                    </option>
                  ))}
                </select>
                <p className="text-xs text-gray-500 dark:text-gray-400">Se selecionado, as tarefas do template serão geradas automaticamente com snapshot. Sem template, projeto é criado sem tarefas.</p>
              </div>
            </CardContent>
          </Card>

          {selectedTemplate && (
            <Card className="max-w-3xl">
              <CardHeader>
                <CardTitle>Pré-visualização — {selectedTemplate.name}</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {(selectedTemplate.phases ?? []).map((p: any) => (
                  <div key={p.id} className="rounded-md border p-3">
                    <p className="mb-2 font-medium">
                      Etapa {p.order} — {p.name}
                    </p>
                    <ul className="space-y-2 text-sm">
                      {(p.tasks ?? []).map((t: any) => (
                        <li key={t.id} className="flex flex-wrap items-center gap-3 border-b pb-2 last:border-0">
                          <input type="checkbox" checked={!excluded[t.id]} onChange={(e) => setExcluded((s) => ({ ...s, [t.id]: !e.target.checked }))} />
                          <span className="flex-1">
                            {t.title} {!t.is_required && <span className="text-xs text-gray-500 dark:text-gray-400">(opcional)</span>}
                            <span className="ml-2 text-xs text-gray-500 dark:text-gray-400">
                              {t.priority} • {t.estimated_days != null ? `${t.estimated_days}d` : ''}
                            </span>
                          </span>
                          <select value={assignments[t.id] ?? ''} onChange={(e) => setAssignments((s) => ({ ...s, [t.id]: e.target.value }))} className="rounded-md border px-2 py-1 text-xs">
                            <option value="">Sem responsável</option>
                            <option value="owner">Responsável sugerido: OWNER</option>
                            <option value="developer">Responsável sugerido: DEVELOPER</option>
                            {users.map((u) => (
                              <option key={u.id} value={u.id}>
                                {u.name}
                              </option>
                            ))}
                          </select>
                        </li>
                      ))}
                    </ul>
                  </div>
                ))}
                <p className="text-xs text-gray-500 dark:text-gray-400">Ajuste responsáveis e desmarque tarefas opcionais. Snapshot preserva o estado atual do template.</p>
              </CardContent>
            </Card>
          )}

          <Button type="submit" disabled={form.processing}>
            {form.processing ? 'Convertendo...' : 'Converter em projeto'}
          </Button>
        </form>
      )}
    </AuthenticatedLayout>
  );
}
