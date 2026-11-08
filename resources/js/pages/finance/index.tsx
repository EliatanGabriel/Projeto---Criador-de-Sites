import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const fmt = (v: string) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(parseFloat(v));

export default function FinanceIndex({ transactions, filters, canManage }: { transactions: any; filters: any; canManage: boolean }) {
  const [month, setMonth] = useState(filters.month ?? new Date().toISOString().slice(0, 7));
  const form = useForm({ month: '', type: '', status: '', search: '' });
  const search = (e: React.FormEvent) => {
    e.preventDefault();
    form.get('/finance', { preserveState: true });
  };

  return (
    <AuthenticatedLayout>
      <Head title="Financeiro" />
      <div className="mb-6 flex items-center justify-between">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Financeiro</h2>
        <div className="flex gap-2">
          <Link href="/finance/report"><Button variant="outline">Relatório mensal</Button></Link>
          <Link href="/finance/categories"><Button variant="outline">Categorias</Button></Link>
          {canManage && <Link href="/finance/create"><Button>Novo lançamento</Button></Link>}
        </div>
      </div>

      <form onSubmit={search} className="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-5">
        <div className="space-y-2">
          <Label>Busca</Label>
          <Input value={form.data.search} onChange={(e) => form.setData('search', e.target.value)} placeholder="Descrição/cliente" />
        </div>
        <div className="space-y-2">
          <Label>Mês (due_at)</Label>
          <Input type="month" value={form.data.month} onChange={(e) => form.setData('month', e.target.value)} />
        </div>
        <div className="space-y-2">
          <Label>Tipo</Label>
          <select value={form.data.type} onChange={(e) => form.setData('type', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
            <option value="">Todos</option>
            <option value="receita">Receita</option>
            <option value="despesa">Despesa</option>
          </select>
        </div>
        <div className="space-y-2">
          <Label>Status</Label>
          <select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
            <option value="">Todos</option>
            <option value="previsto">Previsto</option>
            <option value="pendente">Pendente</option>
            <option value="recebido">Recebido</option>
            <option value="pago">Pago</option>
            <option value="cancelado">Cancelado</option>
          </select>
        </div>
        <div className="flex items-end"><Button type="submit">Filtrar</Button></div>
      </form>

      <Card>
        <CardContent>
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b text-left text-gray-600 dark:text-gray-400">
                <th className="pb-2">Descrição</th>
                <th className="pb-2">Tipo</th>
                <th className="pb-2">Status</th>
                <th className="pb-2">Valor</th>
                <th className="pb-2">Vencimento</th>
              </tr>
            </thead>
            <tbody>
              {(transactions.data ?? []).map((t: any) => (
                <tr key={t.id} className="border-b last:border-0">
                  <td className="py-2 font-medium">
                    <Link href={`/finance/${t.id}`} className="text-blue-600 hover:underline">{t.description}</Link>
                  </td>
                  <td className="py-2">{t.type}</td>
                  <td className="py-2">{t.status}</td>
                  <td className="py-2">{fmt(t.amount)}</td>
                  <td className="py-2">{t.due_at ? new Date(t.due_at).toLocaleDateString('pt-BR') : '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
