import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function SitesCreate({ clients, projects, users }: { clients: { id: string; name: string }[]; projects: { id: string; title: string; client_id: string }[]; users: { id: string; name: string }[] }) {
  const form = useForm({
    name: '',
    description: '',
    client_id: '',
    type: 'institucional',
    status: 'desenvolvimento',
    started_at: '',
    technical_owner_id: '',
    technologies: '',
    notes: '',
    project_ids: [] as string[],
    origin_project_id: '',
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.transform((d) => ({
      ...d,
      technologies: d.technologies ? d.technologies.split(',').map((t) => t.trim()).filter(Boolean) : null,
    }));
    form.post('/sites');
  }

  const filteredProjects = projects.filter((p) => !form.data.client_id || p.client_id === form.data.client_id);

  return (
    <>
      <Head title="Novo site" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">Novo site</h2>
      <form onSubmit={submit} className="space-y-6">
        <Card className="max-w-3xl">
          <CardHeader>
            <CardTitle>Informações gerais</CardTitle>
          </CardHeader>
          <CardContent className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="name">Nome *</Label>
              <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="description">Descrição</Label>
              <textarea id="description" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} className="w-full rounded-md border p-2 text-sm" rows={2} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="client_id">Cliente *</Label>
              <select id="client_id" value={form.data.client_id} onChange={(e) => form.setData('client_id', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm" required>
                <option value="">— Selecione —</option>
                {clients.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="type">Tipo</Label>
              <select id="type" value={form.data.type} onChange={(e) => form.setData('type', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                <option value="institucional">Site institucional</option>
                <option value="landing_page">Landing page</option>
                <option value="loja_virtual">Loja virtual</option>
                <option value="portfolio">Portfólio</option>
                <option value="custom">Site personalizado</option>
                <option value="outros">Outros</option>
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="status">Status *</Label>
              <select id="status" value={form.data.status} onChange={(e) => form.setData('status', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                <option value="desenvolvimento">Desenvolvimento</option>
                <option value="publicado">Publicado</option>
                <option value="suspenso">Suspenso</option>
                <option value="manutencao">Manutenção</option>
                <option value="desativado">Desativado</option>
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="technical_owner_id">Responsável técnico</Label>
              <select id="technical_owner_id" value={form.data.technical_owner_id} onChange={(e) => form.setData('technical_owner_id', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                <option value="">— Selecione —</option>
                {users.map((u) => (
                  <option key={u.id} value={u.id}>
                    {u.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="started_at">Data de início</Label>
              <Input id="started_at" type="date" value={form.data.started_at} onChange={(e) => form.setData('started_at', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="technologies">Tecnologias (vírgulas)</Label>
              <Input id="technologies" value={form.data.technologies} onChange={(e) => form.setData('technologies', e.target.value)} placeholder="laravel, react, postgresql" />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label>Projetos vinculados</Label>
              <div className="max-h-40 overflow-y-auto rounded-md border p-2">
                {filteredProjects.length === 0 && <p className="text-sm text-gray-500 dark:text-gray-400">Nenhum projeto do cliente selecionado.</p>}
                {filteredProjects.map((p) => (
                  <label key={p.id} className="flex items-center gap-2 py-1 text-sm">
                    <input
                      type="checkbox"
                      checked={form.data.project_ids.includes(p.id)}
                      onChange={(e) => {
                        const ids = e.target.checked ? [...form.data.project_ids, p.id] : form.data.project_ids.filter((id) => id !== p.id);
                        form.setData('project_ids', ids);
                        if (form.data.origin_project_id === p.id && !e.target.checked) form.setData('origin_project_id', '');
                      }}
                    />
                    {p.title}
                    <span className="text-xs text-gray-500 dark:text-gray-400">— origem?</span>
                    <input type="radio" name="origin" checked={form.data.origin_project_id === p.id} onChange={() => form.setData('origin_project_id', p.id)} />
                  </label>
                ))}
              </div>
              <p className="text-xs text-gray-500 dark:text-gray-400">Selecione projetos e marque um como origem (máximo 1).</p>
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="notes">Observações internas</Label>
              <textarea id="notes" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} className="w-full rounded-md border p-2 text-sm" rows={2} />
            </div>
          </CardContent>
        </Card>
        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'Salvando...' : 'Salvar site'}
        </Button>
      </form>
    </>
  );
}
