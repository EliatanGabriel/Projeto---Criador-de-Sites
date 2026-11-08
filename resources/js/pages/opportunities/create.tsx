import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const STATUS_OPTIONS = [
  ['nova', 'Nova'],
  ['em_contato', 'Em contato'],
  ['em_negociacao', 'Em negociação'],
  ['aguardando_cliente', 'Aguardando cliente'],
  ['ganha', 'Ganha'],
  ['perdida', 'Perdida'],
  ['cancelada', 'Cancelada'],
] as const;

export default function OpportunitiesCreate({ clients, users }: { clients: { id: string; name: string }[]; users: { id: string; name: string }[] }) {
  const form = useForm({
    title: '',
    client_id: '',
    contact_name: '',
    responsible_id: '',
    service_type: '',
    description: '',
    estimated_value: '',
    negotiated_value: '',
    opened_at: new Date().toISOString().slice(0, 10),
    expected_close_at: '',
    status: 'nova',
    notes: '',
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.post('/opportunities');
  }

  return (
    <AuthenticatedLayout>
      <Head title="Nova oportunidade" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">Nova oportunidade</h2>
      <Card className="max-w-3xl">
        <CardHeader>
          <CardTitle>Dados da oportunidade</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="title">Título *</Label>
              <Input id="title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} required />
              {form.errors.title && <p className="text-sm text-red-600">{form.errors.title}</p>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="client_id">Cliente (opcional)</Label>
              <select id="client_id" value={form.data.client_id} onChange={(e) => form.setData('client_id', e.target.value || null as any)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
                <option value="">— Sem cliente vinculado —</option>
                {clients.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="contact_name">Nome do contato (sem cliente)</Label>
              <Input id="contact_name" value={form.data.contact_name} onChange={(e) => form.setData('contact_name', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="responsible_id">Responsável comercial</Label>
              <select id="responsible_id" value={form.data.responsible_id} onChange={(e) => form.setData('responsible_id', e.target.value || null as any)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
                <option value="">— Selecione —</option>
                {users.map((u) => (
                  <option key={u.id} value={u.id}>
                    {u.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="status">Status *</Label>
              <select id="status" value={form.data.status} onChange={(e) => form.setData('status', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
                {STATUS_OPTIONS.map(([v, l]) => (
                  <option key={v} value={v}>
                    {l}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="service_type">Tipo de serviço</Label>
              <Input id="service_type" value={form.data.service_type} onChange={(e) => form.setData('service_type', e.target.value)} placeholder="Site institucional" />
            </div>
            <div className="space-y-2">
              <Label htmlFor="estimated_value">Valor estimado (BRL)</Label>
              <Input id="estimated_value" type="number" step="0.01" min="0" value={form.data.estimated_value} onChange={(e) => form.setData('estimated_value', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="negotiated_value">Valor negociado (BRL)</Label>
              <Input id="negotiated_value" type="number" step="0.01" min="0" value={form.data.negotiated_value} onChange={(e) => form.setData('negotiated_value', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="opened_at">Data de abertura</Label>
              <Input id="opened_at" type="date" value={form.data.opened_at} onChange={(e) => form.setData('opened_at', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="expected_close_at">Previsão de fechamento</Label>
              <Input id="expected_close_at" type="date" value={form.data.expected_close_at} onChange={(e) => form.setData('expected_close_at', e.target.value)} />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="description">Descrição da necessidade</Label>
              <textarea id="description" value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm" rows={3} />
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="notes">Observações</Label>
              <textarea id="notes" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm" rows={2} />
            </div>
            <div className="sm:col-span-2">
              <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Salvando...' : 'Criar oportunidade'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
