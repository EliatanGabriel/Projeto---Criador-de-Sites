import { useForm, Head, Link } from '@inertiajs/react';
import GuestLayout from '@/layouts/guest-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';

export default function Login({ status, canResetPassword, panel }: { status?: string; canResetPassword?: boolean; panel?: string | null }) {
  const form = useForm({
    email: '',
    password: '',
    remember: false,
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.post('/login', { onFinish: () => form.reset('password') });
  }

  return (
    <GuestLayout>
      <Head title="Entrar" />
      <h1 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
        {panel === 'admin' ? 'Entrar no Painel Admin' : panel === 'commercial' ? 'Entrar no Painel Comercial' : panel === 'developer' ? 'Entrar no Painel Developer' : 'Entrar na plataforma'}
      </h1>
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Informe suas credenciais de acesso.</p>

      {status && (
        <Alert className="mt-4" variant="success">
          <AlertDescription>{status}</AlertDescription>
        </Alert>
      )}

      <form onSubmit={submit} className="mt-6 space-y-4">
        <div className="space-y-2">
          <Label htmlFor="email">E-mail</Label>
          <Input id="email" type="email" autoComplete="email" required value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} placeholder="voce@empresa.com" />
          {form.errors.email && <p className="text-sm text-red-600">{form.errors.email}</p>}
        </div>
        <div className="space-y-2">
          <Label htmlFor="password">Senha</Label>
          <Input id="password" type="password" autoComplete="current-password" required value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} placeholder="••••••••" />
          {form.errors.password && <p className="text-sm text-red-600">{form.errors.password}</p>}
        </div>
        <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
          <input type="checkbox" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} className="rounded border-gray-300 dark:border-gray-700" />
          Manter conectado
        </label>
        <Button type="submit" className="w-full" disabled={form.processing}>
          {form.processing ? 'Entrando...' : 'Entrar'}
        </Button>
        {canResetPassword && (
          <p className="text-center text-sm">
            <Link href="/forgot-password" className="text-blue-600 hover:underline">
              Esqueci minha senha
            </Link>
          </p>
        )}
      </form>
      <p className="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">Contas criadas apenas por administrador autorizado.</p>
    </GuestLayout>
  );
}
