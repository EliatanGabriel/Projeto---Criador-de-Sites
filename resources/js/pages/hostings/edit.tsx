import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function HostingsEdit({ hosting }: { hosting: any }) {
  const form = useForm({
    provider: hosting.provider ?? '',
    name: hosting.name ?? '',
    plan: hosting.plan ?? '',
    account_identifier: hosting.account_identifier ?? '',
    status: hosting.status ?? 'ativo',
    notes: hosting.notes ?? '',
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.put(`/hostings/${hosting.id}`);
  }

  return (
    <AuthenticatedLayout>
      <Head title="Editar hospedagem" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">Editar hospedagem</h2>
      <Card className="max-w-2xl">
        <CardHeader>
          <CardTitle>{hosting.name}</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="provider">Provedor *</Label>
              <Input id="provider" value={form.data.provider} onChange={(e) => form.setData('provider', e.target.value)} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="name">Nome *</Label>
              <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="plan">Plano</Label>
              <Input id="plan" value={form.data.plan} onChange={(e) => form.setData('plan', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="account_identifier">Conta</Label>
              <Input id="account_identifier" value={form.data.account_identifier} onChange={(e) => form.setData('account_identifier', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="status">Status *</Label>
              <select id="status" value={form.data.status} onChange={(e) => form.setData('status', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                <option value="ativo">Ativo</option>
                <option value="suspenso">Suspenso</option>
                <option value="cancelado">Cancelado</option>
              </select>
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="notes">Observações</Label>
              <textarea id="notes" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} className="w-full rounded-md border p-2 text-sm" rows={2} />
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
