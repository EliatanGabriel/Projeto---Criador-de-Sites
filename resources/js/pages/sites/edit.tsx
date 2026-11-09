import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function SitesEdit({ site, users }: { site: any; users: { id: string; name: string }[] }) {
  const form = useForm({
    name: site.name ?? '',
    description: site.description ?? '',
    type: site.type ?? 'institucional',
    status: site.status ?? 'desenvolvimento',
    started_at: (site.started_at ?? '').slice(0, 10),
    published_at: (site.published_at ?? '').slice(0, 10),
    technical_owner_id: site.technical_owner_id ?? '',
    technologies: (site.technologies ?? []).join(', '),
    notes: site.notes ?? '',
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.transform((d) => ({ ...d, technologies: d.technologies ? d.technologies.split(',').map((t: string) => t.trim()).filter(Boolean) : null }));
    form.put(`/sites/${site.id}`);
  }

  return (
    <>
      <Head title="Editar site" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">Editar site</h2>
      <form onSubmit={submit} className="space-y-6">
        <Card className="max-w-3xl">
          <CardHeader>
            <CardTitle>{site.name}</CardTitle>
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
              <Label htmlFor="started_at">Início</Label>
              <Input id="started_at" type="date" value={form.data.started_at} onChange={(e) => form.setData('started_at', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="published_at">Publicação</Label>
              <Input id="published_at" type="date" value={form.data.published_at} onChange={(e) => form.setData('published_at', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="technologies">Tecnologias</Label>
              <Input id="technologies" value={form.data.technologies} onChange={(e) => form.setData('technologies', e.target.value)} />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="notes">Observações</Label>
              <textarea id="notes" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} className="w-full rounded-md border p-2 text-sm" rows={2} />
            </div>
          </CardContent>
        </Card>
        <Button type="submit" disabled={form.processing}>
          {form.processing ? 'Salvando...' : 'Salvar'}
        </Button>
      </form>
    </>
  );
}
