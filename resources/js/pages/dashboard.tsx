import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

export default function Dashboard() {
  return (
    <AuthenticatedLayout>
      <Head title="Dashboard" />
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-gray-900">Dashboard</h2>
        <p className="mt-1 text-sm text-gray-600">Visão geral da operação — Fase 1 (fundação). Os módulos de negócio serão ativados nas próximas fases.</p>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader>
            <CardDescription>Clientes</CardDescription>
            <CardTitle>Em breve</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-sm text-gray-600">Módulo disponível na Fase 3.</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardDescription>Demandas</CardDescription>
            <CardTitle>Em breve</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-sm text-gray-600">Módulo disponível na Fase 4.</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardDescription>Projetos ativos</CardDescription>
            <CardTitle>Em breve</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-sm text-gray-600">Módulo disponível na Fase 5.</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardDescription>Tarefas pendentes</CardDescription>
            <CardTitle>Em breve</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-sm text-gray-600">Módulo disponível na Fase 5.</p>
          </CardContent>
        </Card>
      </div>

      <Card className="mt-6">
        <CardHeader>
          <CardTitle>Ambiente operacional</CardTitle>
          <CardDescription>Base validada: autenticação, permissões, layout administrativo e navegação responsiva.</CardDescription>
        </CardHeader>
        <CardContent className="space-y-2 text-sm text-gray-700">
          <p>✔ Autenticação via Laravel Fortify (login, logout, recuperação de senha).</p>
          <p>✔ Autorização via Spatie Permission (roles: OWNER, COMMERCIAL, DEVELOPER) validada no backend.</p>
          <p>✔ Layout administrativo com sidebar recolhível e navegação mobile.</p>
          <p>✔ Feedback visual de sucesso/erro e estados de carregamento (botões desabilitados durante processamento).</p>
          <p className="text-gray-500">Nenhum módulo de negócio foi implementado nesta fase, conforme restrições aprovadas.</p>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
