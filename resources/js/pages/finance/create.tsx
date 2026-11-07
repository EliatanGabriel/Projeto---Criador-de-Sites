import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function FinanceCreate({ categories, clients, projects }: { categories: any[]; clients: any[]; projects: any[] }) {
  const form = useForm({
    type: 'receita',
    category_id: categories[0]?.id ?? '',
    client_id: '',
    project_id: '',
    description: '',
    amount: '',
    status: 'previsto',
    due_at: '',
    notes: '',
  });

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    form.post('/finance');
  };

  return (
    <AuthenticatedLayout>
      <Head title="Novo lançamento" />
      <div className="mb-6"><Link href="/finance" className="text-blue-600 hover:underline">← Voltar</Link></div>
      <Card>
        <CardHeader><CardTitle>Novo lançamento financeiro</CardTitle></CardHeader>
        <CardContent>
          <form onSubmit={submit} className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div className="space-y-2">
              <Label>Tipo *</Label>
              <select value={form.data.type} onChange={(e) => form.setData('type', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                <option value="receita">Receita</option>
                <option value="despesa">Despesa</option>
              </select>
            </div>
            <div className="space-y-2">
              <Label>Categoria *</Label>
              <select value={form.data.category_id} onChange={(e) => form.setData('category_id', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                {(categories ?? []).filter((c: any) => c.type === form.data.type).map((c: any) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label>Cliente</Label>
              <select value={form.data.client_id} onChange={(e) => form.setData('client_id', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                <option value="">— Sem cliente —</option>
                {clients.map((c: any) => (
                  <option key={c.id} value={c.id}>{c.name}</option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label>Projeto</Label>
              <select value={form.data.project_id} onChange={(e) => form.setData('project_id', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                <option value="">— Sem projeto —</option>
                {projects.map((p: any) => (
                  <option key={p.id} value={p.id}>{p.title}</option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label>Descrição *</Label>
              <Input value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} required />
            </div>
            <div className="space-y-2">
              <Label>Valor (R$) *</Label>
              <Input type="number" min="0.01" step="0.01" value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} required />
            </div>
            <div className="space-y-2">
              <Label>Status</Label>
              <select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                <option value="previsto">Previsto</option>
                <option value="pendente">Pendente</option>
              </select>
            </div>
            <div className="space-y-2">
              <Label>Vencimento</Label>
              <Input type="date" value={form.data.due_at} onChange={(e) => form.setData('due_at', e.target.value)} />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label>Observações</Label>
              <Input value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} />
            </div>
            <Button type="submit" disabled={form.processing}>Salvar</Button>
          </form>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
