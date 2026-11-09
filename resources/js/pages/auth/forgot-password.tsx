import { useForm, Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';

export default function ForgotPassword({ status }: { status?: string }) {
  const form = useForm({ email: '' });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.post('/forgot-password');
  }

  return (
    <>
      <Head title="Recuperar senha" />
      <h1 className="text-xl font-semibold text-gray-900 dark:text-gray-100">Recuperar senha</h1>
      <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Informe seu e-mail para receber o link de redefinição.</p>

      {status && (
        <Alert className="mt-4" variant="success">
          <AlertDescription>{status}</AlertDescription>
        </Alert>
      )}

      <form onSubmit={submit} className="mt-6 space-y-4">
        <div className="space-y-2">
          <Label htmlFor="email">E-mail</Label>
          <Input id="email" type="email" required value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} placeholder="voce@empresa.com" />
          {form.errors.email && <p className="text-sm text-red-600">{form.errors.email}</p>}
        </div>
        <Button type="submit" className="w-full" disabled={form.processing}>
          {form.processing ? 'Enviando...' : 'Enviar link'}
        </Button>
        <p className="text-center text-sm">
          <Link href="/login" className="text-blue-600 hover:underline">
            Voltar ao login
          </Link>
        </p>
      </form>
    </>
  );
}
