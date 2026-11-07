import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Client } from '@/types';

export default function ClientsEdit({ client }: { client: Client }) {
  const form = useForm({
    name: client.name ?? '',
    type: (client.type ?? 'pj') as 'pj' | 'pf',
    document: client.document ?? '',
    email: client.email ?? '',
    phone: client.phone ?? '',
    contact_name: client.contact_name ?? '',
    address: client.address ?? '',
    city: client.city ?? '',
    state: client.state ?? '',
    zip: client.zip ?? '',
    notes: client.notes ?? '',
    status: (client.status ?? 'ativo') as 'ativo' | 'inativo',
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.put(`/clients/${client.id}`);
  }

  return (
    <AuthenticatedLayout>
      <Head title="Editar cliente" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900">Editar cliente</h2>
      <Card className="max-w-3xl">
        <CardHeader>
          <CardTitle>{client.name}</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={submit} className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="name">Nome ou razão social *</Label>
              <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
              {form.errors.name && <p className="text-sm text-red-600">{form.errors.name}</p>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="type">Tipo *</Label>
              <select id="type" value={form.data.type} onChange={(e) => form.setData('type', e.target.value as 'pj' | 'pf')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="pj">Pessoa jurídica</option>
                <option value="pf">Pessoa física</option>
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="document">CPF/CNPJ</Label>
              <Input id="document" value={form.data.document} onChange={(e) => form.setData('document', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="email">E-mail</Label>
              <Input id="email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="phone">Telefone</Label>
              <Input id="phone" value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="contact_name">Contato principal</Label>
              <Input id="contact_name" value={form.data.contact_name} onChange={(e) => form.setData('contact_name', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="status">Status *</Label>
              <select id="status" value={form.data.status} onChange={(e) => form.setData('status', e.target.value as 'ativo' | 'inativo')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="ativo">Ativo</option>
                <option value="inativo">Inativo</option>
              </select>
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="address">Endereço</Label>
              <Input id="address" value={form.data.address} onChange={(e) => form.setData('address', e.target.value)} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="city">Cidade</Label>
              <Input id="city" value={form.data.city} onChange={(e) => form.setData('city', e.target.value)} />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="state">UF</Label>
                <Input id="state" value={form.data.state} onChange={(e) => form.setData('state', e.target.value.toUpperCase())} maxLength={2} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="zip">CEP</Label>
                <Input id="zip" value={form.data.zip} onChange={(e) => form.setData('zip', e.target.value)} />
              </div>
            </div>
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="notes">Observações internas</Label>
              <textarea id="notes" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" rows={3} />
            </div>
            <div className="sm:col-span-2">
              <Button type="submit" disabled={form.processing}>
                {form.processing ? 'Salvando...' : 'Salvar alterações'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
