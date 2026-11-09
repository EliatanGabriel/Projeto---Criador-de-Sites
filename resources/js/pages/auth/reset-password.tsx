import { useForm, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function ResetPassword({ email, token }: { email?: string; token?: string }) {
  const form = useForm({
    token: token ?? '',
    email: email ?? '',
    password: '',
    password_confirmation: '',
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.post('/reset-password');
  }

  return (
    <>
      <Head title="Redefinir senha" />
      <h1 className="text-xl font-semibold text-gray-900 dark:text-gray-100">Redefinir senha</h1>
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Crie uma nova senha para sua conta.</p>

      <form onSubmit={submit} className="mt-6 space-y-4">
        <div className="space-y-2">
          <Label htmlFor="email">E-mail</Label>
          <Input id="email" type="email" required value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
          {form.errors.email && <p className="text-sm text-red-600">{form.errors.email}</p>}
        </div>
        <div className="space-y-2">
          <Label htmlFor="password">Nova senha</Label>
          <Input id="password" type="password" required autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />
          {form.errors.password && <p className="text-sm text-red-600">{form.errors.password}</p>}
        </div>
        <div className="space-y-2">
          <Label htmlFor="password_confirmation">Confirmar nova senha</Label>
          <Input id="password_confirmation" type="password" required autoComplete="new-password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} />
        </div>
        <Button type="submit" className="w-full" disabled={form.processing}>
          {form.processing ? 'Salvando...' : 'Redefinir senha'}
        </Button>
      </form>
    </>
  );
}
