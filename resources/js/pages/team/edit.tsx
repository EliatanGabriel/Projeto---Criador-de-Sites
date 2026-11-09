import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function TeamEdit({ user, roles, currentRole }: { user: { id: string; name: string; email: string; is_active: boolean }; roles: string[]; currentRole: string | null }) {
  const form = useForm({
    name: user.name,
    email: user.email,
    role: currentRole ?? 'COMMERCIAL',
    is_active: user.is_active,
    password: '',
    password_confirmation: '',
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.put(`/team/${user.id}`);
  }

  return (
    <>
      <Head title="Editar usuário" />
      <h2 className="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">Editar usuário</h2>
      <Card className="max-w-2xl">
        <CardHeader>
          <CardTitle>{user.name}</CardTitle>
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
            <div className="space-y-2">
              <Label htmlFor="role">Perfil</Label>
              <select id="role" value={form.data.role} onChange={(e) => form.setData('role', e.target.value)} className="w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm">
                {roles.map((r) => (
                  <option key={r} value={r}>
                    {r}
                  </option>
                ))}
              </select>
            </div>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} />
              Usuário ativo
            </label>
            <div className="space-y-2 border-t pt-4">
              <Label htmlFor="password">Redefinir senha (opcional)</Label>
              <Input id="password" type="password" autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} placeholder="Deixe vazio para manter a atual" />
              {form.errors.password && <p className="text-sm text-red-600">{form.errors.password}</p>}
              <p className="text-xs text-gray-500 dark:text-gray-400">A senha atual não pode ser exibida (fica salva como hash). Ao redefinir, informe a nova senha ao usuário por canal seguro.</p>
            </div>
            <div className="space-y-2">
              <Label htmlFor="password_confirmation">Confirmar nova senha</Label>
              <Input id="password_confirmation" type="password" autoComplete="new-password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} />
              {form.errors.password_confirmation && <p className="text-sm text-red-600">{form.errors.password_confirmation}</p>}
            </div>
            <Button type="submit" disabled={form.processing}>
              {form.processing ? 'Salvando...' : 'Salvar alterações'}
            </Button>
          </form>
        </CardContent>
      </Card>
    </>
  );
}
