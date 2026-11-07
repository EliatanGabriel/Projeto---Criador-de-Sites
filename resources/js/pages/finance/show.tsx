import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

const fmt = (v: string) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(parseFloat(v));

export default function FinanceShow({ transaction, canManage }: { transaction: any; canManage: boolean }) {
  const payForm = useForm({ amount: '', paid_at: new Date().toISOString().slice(0, 10), method: 'pix', reference: '', notes: '' });

  const submitPayment = (e: React.FormEvent) => {
    e.preventDefault();
    payForm.post(`/finance/${transaction.id}/payments`);
  };

  return (
    <AuthenticatedLayout>
      <Head title={transaction.description} />
      <div className="mb-6"><Link href="/finance" className="text-blue-600 hover:underline">← Voltar para Financeiro</Link></div>
      <Card>
        <CardHeader><CardTitle>{transaction.description}</CardTitle></CardHeader>
        <CardContent className="space-y-2 text-sm">
          <p><span className="font-medium">Tipo:</span> {transaction.type} • <span className="font-medium">Status:</span> {transaction.status}</p>
          <p><span className="font-medium">Valor:</span> {fmt(transaction.amount)} • <span className="font-medium">Categoria:</span> {transaction.category?.name}</p>
          <p><span className="font-medium">Cliente:</span> {transaction.client?.name ?? '—'} • <span className="font-medium">Projeto:</span> {transaction.project?.title ?? '—'}</p>
          <p><span className="font-medium">Vencimento:</span> {transaction.due_at ? new Date(transaction.due_at).toLocaleDateString('pt-BR') : '—'} • <span className="font-medium">Pago em:</span> {transaction.paid_at ? new Date(transaction.paid_at).toLocaleDateString('pt-BR') : '—'}</p>
          <p><span className="font-medium">Observações:</span> {transaction.notes ?? '—'}</p>

          {(transaction.payments ?? []).length > 0 && (
            <div className="pt-3">
              <p className="font-medium">Pagamentos registrados</p>
              <ul className="mt-1 space-y-1">
                {(transaction.payments ?? []).map((p: any) => (
                  <li key={p.id}>{fmt(p.amount)} — {new Date(p.paid_at).toLocaleDateString('pt-BR')} {p.method ? `(${p.method})` : ''}</li>
                ))}
              </ul>
            </div>
          )}

          {canManage && transaction.status !== 'cancelado' && (
            <div className="pt-4">
              <p className="font-medium">Registrar pagamento</p>
              <form onSubmit={submitPayment} className="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div className="space-y-2">
                  <Label>Valor</Label>
                  <Input type="number" min="0.01" step="0.01" value={payForm.data.amount} onChange={(e) => payForm.setData('amount', e.target.value)} required />
                </div>
                <div className="space-y-2">
                  <Label>Data</Label>
                  <Input type="date" value={payForm.data.paid_at} onChange={(e) => payForm.setData('paid_at', e.target.value)} required />
                </div>
                <div className="space-y-2">
                  <Label>Forma</Label>
                  <Input value={payForm.data.method} onChange={(e) => payForm.setData('method', e.target.value)} />
                </div>
                <div className="space-y-2">
                  <Label>Referência</Label>
                  <Input value={payForm.data.reference} onChange={(e) => payForm.setData('reference', e.target.value)} />
                </div>
                <Button type="submit" disabled={payForm.processing}>Registrar</Button>
              </form>
            </div>
          )}

          {canManage && !transaction.payments?.length && transaction.status !== 'cancelado' && (
            <form onSubmit={(e) => { e.preventDefault(); useForm({}).post(`/finance/${transaction.id}/cancel`); }} className="pt-4">
              <Button type="submit" variant="outline">Cancelar lançamento</Button>
            </form>
          )}
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
