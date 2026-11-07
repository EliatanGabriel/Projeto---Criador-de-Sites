import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function TeamCreate({ roles }: { roles: string[] }) {
  const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: roles[0] ?? 'COMMERCIAL',
    is_active: true,
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.post('/team', { onFinish: () => form.reset('password', 'password_confirmation') });
  }

  return (
    <AuthenticatedLayout>
      <Head title="Novo usuário" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900">Novo usuário interno</h2>
      <Card className="max-w-2xl">
        <CardHeader>
          <CardTitle>Dados do usuário</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={submit} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="name">Nome</Label>
              <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
              {form.errors.name && <p className="text-sm text-red-600">{form.errors.name}</p>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="email">E-mail</Label>
              <Input id="email" type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} required />
              {form.errors.email && <p className="text-sm text-red-600">{form.errors.email}</p>}
            </div>
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="password">Senha</Label>
                <Input id="password" type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} required />
                {form.errors.password && <p className="text-sm text-red-600">{form.errors.password}</p>}
              </div>
              <div className="space-y-2">
                <Label htmlFor="password_confirmation">Confirmar senha</Label>
                <Input id="password_confirmation" type="password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} required />
              </div>
            </div>
            <div className="space-y-2">
              <Label htmlFor="role">Perfil</Label>
              <select id="role" value={form.data.role} onChange={(e) => form.setData('role', e.target.value)} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                {roles.map((r) => (
                  <option key={r} value={r}>
                    {r}
                  </option>
                ))}
              </select>
              {form.errors.role && <p className="text-sm text-red-600">{form.errors.role}</p>}
            </div>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} />
              Usuário ativo
            </label>
            <Button type="submit" disabled={form.processing}>
              {form.processing ? 'Salvando...' : 'Criar usuário'}
            </Button>
          </form>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
