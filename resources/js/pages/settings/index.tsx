import { Head, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';

export default function Settings({ settings, flash }: any) {
  const form = useForm({
    company_name: settings.company_name || '',
    company_email: settings.company_email || '',
    notifications_enabled: settings.notifications_enabled ?? true,
    maintenance_mode: settings.maintenance_mode ?? false,
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    form.put('/settings');
  }

  return (
    <>
      <Head title="Configurações" />
      <div className="max-w-3xl mx-auto space-y-6">
        <div>
          <h1 className="text-2xl font-bold">Configurações</h1>
          <p className="text-gray-500 dark:text-gray-400 mt-1">Gerencie as configurações da aplicação</p>
        </div>

        {flash?.success && (
          <Alert variant="success">
            <AlertDescription>{flash.success}</AlertDescription>
          </Alert>
        )}

        <form onSubmit={submit}>
          <Card>
            <CardHeader>
              <CardTitle>Informações da Empresa</CardTitle>
              <CardDescription>Dados básicos da sua empresa</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="company_name">Nome da Empresa</Label>
                <Input
                  id="company_name"
                  value={form.data.company_name}
                  onChange={(e) => form.setData('company_name', e.target.value)}
                  placeholder="WebStudio OS"
                />
                {form.errors.company_name && <p className="text-sm text-red-600">{form.errors.company_name}</p>}
              </div>

              <div className="space-y-2">
                <Label htmlFor="company_email">E-mail de Contato</Label>
                <Input
                  id="company_email"
                  type="email"
                  value={form.data.company_email}
                  onChange={(e) => form.setData('company_email', e.target.value)}
                  placeholder="contato@webstudio.com"
                />
                {form.errors.company_email && <p className="text-sm text-red-600">{form.errors.company_email}</p>}
              </div>

              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="notifications_enabled"
                  checked={form.data.notifications_enabled}
                  onChange={(e) => form.setData('notifications_enabled', e.target.checked)}
                  className="rounded border-gray-300 dark:border-gray-700"
                />
                <Label htmlFor="notifications_enabled">Notificações ativadas</Label>
              </div>

              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="maintenance_mode"
                  checked={form.data.maintenance_mode}
                  onChange={(e) => form.setData('maintenance_mode', e.target.checked)}
                  className="rounded border-gray-300 dark:border-gray-700"
                />
                <Label htmlFor="maintenance_mode">Modo de manutenção</Label>
              </div>

              <div className="pt-4">
                <Button type="submit" disabled={form.processing}>
                  {form.processing ? 'Salvando...' : 'Salvar Configurações'}
                </Button>
              </div>
            </CardContent>
          </Card>
        </form>
      </div>
    </>
  );
}
