import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export default function OpportunitiesShow({ opportunity }: { opportunity: any }) {
  const statusForm = useForm({ status: opportunity.status });

  function updateStatus(status: string) {
    statusForm.transform(() => ({ status }));
    statusForm.patch(`/opportunities/${opportunity.id}/status`);
  }

  const timeline = [...(opportunity.activities ?? [])].reverse();

  return (
    <AuthenticatedLayout>
      <Head title={opportunity.title} />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900">{opportunity.title}</h2>
          <p className="mt-1 text-sm text-gray-600">
            {opportunity.client?.name ?? opportunity.contact_name ?? 'Sem cliente'} • {opportunity.status} • Abertura {opportunity.opened_at ? new Date(opportunity.opened_at).toLocaleDateString('pt-BR') : '—'}
          </p>
        </div>
        <Link href={`/opportunities/${opportunity.id}/edit`}>
          <Button>Editar</Button>
        </Link>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Detalhes</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            <p><span className="font-medium">Responsável:</span> {opportunity.responsible?.name ?? '—'}</p>
            <p><span className="font-medium">Serviço:</span> {opportunity.service_type ?? '—'}</p>
            <p><span className="font-medium">Estimado:</span> {opportunity.estimated_value ? Number(opportunity.estimated_value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '—'}</p>
            <p><span className="font-medium">Negociado:</span> {opportunity.negotiated_value ? Number(opportunity.negotiated_value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '—'}</p>
            <p><span className="font-medium">Previsão:</span> {opportunity.expected_close_at ? new Date(opportunity.expected_close_at).toLocaleDateString('pt-BR') : '—'}</p>
            <p><span className="font-medium">Descrição:</span> {opportunity.description ?? '—'}</p>
            <p><span className="font-medium">Observações:</span> {opportunity.notes ?? '—'}</p>
            <div className="flex flex-wrap gap-2 pt-2">
              <span className="text-xs text-gray-500">Atualizar status:</span>
              {['nova', 'em_contato', 'em_negociacao', 'aguardando_cliente', 'ganha', 'perdida', 'cancelada'].map((s) => (
                <button
                  key={s}
                  onClick={() => updateStatus(s)}
                  disabled={statusForm.processing || opportunity.status === s}
                  className={`rounded-full px-2.5 py-1 text-xs ${opportunity.status === s ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
                >
                  {s}
                </button>
              ))}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Histórico</CardTitle>
          </CardHeader>
          <CardContent>
            {timeline.length === 0 ? (
              <p className="text-sm text-gray-500">Sem histórico registrado.</p>
            ) : (
              <ul className="space-y-3">
                {timeline.map((a: any) => (
                  <li key={a.id} className="border-b pb-2 last:border-0 text-sm">
                    <p className="font-medium">{a.action}</p>
                    {a.description && <p className="text-gray-600">{a.description}</p>}
                    <p className="text-xs text-gray-500">
                      {a.user?.name ?? 'Sistema'} • {new Date(a.created_at).toLocaleString('pt-BR')}
                    </p>
                  </li>
                ))}
              </ul>
            )}
          </CardContent>
        </Card>
      </div>
    </AuthenticatedLayout>
  );
}
