import { Head, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function FinanceCategories({ categories, canManage }: { categories: any[]; canManage: boolean }) {
  const form = useForm({ name: '', type: 'receita' });

  return (
    <>
      <Head title="Categorias financeiras" />
      <div className="mb-6"><h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Categorias financeiras</h2></div>
      <Card>
        <CardHeader><CardTitle>Categorias</CardTitle></CardHeader>
        <CardContent className="space-y-4">
          {canManage && (
            <form onSubmit={(e) => { e.preventDefault(); form.post('/finance/categories'); }} className="grid grid-cols-1 gap-3 sm:grid-cols-3">
              <div className="space-y-2">
                <Label>Nome *</Label>
                <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
              </div>
              <div className="space-y-2">
                <Label>Tipo *</Label>
                <select value={form.data.type} onChange={(e) => form.setData('type', e.target.value)} className="w-full rounded-md border px-3 py-2 text-sm">
                  <option value="receita">Receita</option>
                  <option value="despesa">Despesa</option>
                </select>
              </div>
              <Button type="submit" disabled={form.processing}>Adicionar</Button>
            </form>
          )}
          <table className="w-full text-sm">
            <thead><tr className="border-b text-left text-gray-600 dark:text-gray-400"><th className="pb-2">Nome</th><th className="pb-2">Tipo</th></tr></thead>
            <tbody>
              {(categories ?? []).map((c: any) => (
                <tr key={c.id} className="border-b last:border-0"><td className="py-2 font-medium">{c.name}</td><td className="py-2">{c.type}</td></tr>
              ))}
            </tbody>
          </table>
        </CardContent>
      </Card>
    </>
  );
}
