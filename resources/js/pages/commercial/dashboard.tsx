import { Head, Link, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface DemandItem {
  id: string;
  title: string;
  status: string;
  priority: string;
  submitted_at: string | null;
  created_at: string;
  client?: { name: string } | null;
  waiting_days?: number;
  can_edit?: boolean;
  can_submit?: boolean;
}

interface OpportunityItem {
  id: string;
  title: string;
  status: string;
  estimated_value: string | number | null;
  client?: { name: string } | null;
}

const statusLabels: Record<string, string> = {
  submitted: 'Enviada',
  under_review: 'Em análise',
  needs_information: 'Aguardando info',
  draft: 'Rascunho',
  approved: 'Aprovada',
  rejected: 'Recusada',
  cancelled: 'Cancelada',
  nova: 'Nova',
  em_contato: 'Em contato',
  em_negociacao: 'Em negociação',
  aguardando_cliente: 'Aguardando cliente',
  ganha: 'Ganha',
  perdida: 'Perdida',
  cancelada: 'Cancelada',
};

function StatusBadge({ status }: { status: string }) {
  const styles: Record<string, string> = {
    submitted: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    under_review: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    needs_information: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
    approved: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    draft: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300',
    rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    cancelled: 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
  };
  return (
    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${styles[status] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300'}`}>
      {statusLabels[status] ?? status}
    </span>
  );
}

function PriorityText({ priority }: { priority: string }) {
  const colors: Record<string, string> = {
    urgent: 'text-red-600 font-semibold dark:text-red-400',
    high: 'text-orange-600 dark:text-orange-400',
    medium: 'text-gray-600 dark:text-gray-300',
    low: 'text-gray-400 dark:text-gray-500',
  };
  return <span className={colors[priority] ?? ''}>{priority}</span>;
}

export default function CommercialDashboard({
  stats,
  demands,
  needsInfoDemands,
  draftDemands,
  openOpportunities,
  financeSummary,
}: {
  stats: { draft: number; submitted: number; needs_information: number; approved: number; rejected: number; total: number; open_opportunities: number; pipeline_estimated: number; active_clients: number };
  demands: DemandItem[];
  needsInfoDemands: DemandItem[];
  draftDemands: DemandItem[];
  openOpportunities: OpportunityItem[];
  financeSummary: { receita_pendente: number; receita_vencida: number; receita_recebida_mes: number };
}) {
  const form = useForm({});
  const brl = (v: number) => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  const date = (v: string | null) => (v ? new Date(v).toLocaleString('pt-BR') : '—');

  const submitDemand = (id: string) => {
    form.post(`/demands/${id}/submit`, { preserveScroll: true });
  };

  const DemandRow = ({ d, showActions }: { d: DemandItem; showActions?: boolean }) => (
    <li className="border-b pb-2 last:border-0">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div>
          <Link href={`/demands/${d.id}`} className="font-medium text-blue-600 hover:underline">
            {d.title}
          </Link>
          <p className="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
            <span>{d.client?.name ?? 'Sem cliente'}</span>
            <StatusBadge status={d.status} />
            <PriorityText priority={d.priority} />
            <span>{date(d.submitted_at ?? d.created_at)}</span>
            {(d.waiting_days ?? 0) > 0 && (
              <span className={(d.waiting_days ?? 0) >= 7 ? 'font-semibold text-red-600 dark:text-red-400' : ''}>
                {d.waiting_days}d
              </span>
            )}
          </p>
        </div>
        {showActions && (
          <div className="flex items-center gap-1">
            {d.can_edit && (
              <Link href={`/demands/${d.id}/edit`}>
                <Button size="sm" variant="outline">Editar</Button>
              </Link>
            )}
            {d.can_submit && (
              <Button size="sm" variant="secondary" disabled={form.processing} onClick={() => submitDemand(d.id)}>
                {d.status === 'needs_information' ? 'Reenviar' : 'Enviar'}
              </Button>
            )}
          </div>
        )}
      </div>
    </li>
  );

  return (
    <>
      <Head title="Painel Comercial" />
      <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Painel Comercial</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Funil comercial, demandas e financeiro resumido.</p>
        </div>
        <div className="flex flex-wrap items-center gap-2">
          <Link href="/opportunities/create">
            <Button variant="outline">+ Nova oportunidade</Button>
          </Link>
          <Link href="/demands/create">
            <Button>+ Nova demanda</Button>
          </Link>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {([
          ['Rascunhos', stats.draft, '/demands?status=draft'],
          ['Enviadas / em análise', stats.submitted, '/demands?status=submitted'],
          ['Precisam de informação', stats.needs_information, '/demands?status=needs_information'],
          ['Aprovadas', stats.approved, '/demands?status=approved'],
          ['Recusadas', stats.rejected, '/demands?status=rejected'],
          ['Oportunidades abertas', stats.open_opportunities, '/opportunities'],
          ['Pipeline estimado', brl(stats.pipeline_estimated), '/opportunities'],
          ['Clientes ativos', stats.active_clients, '/clients'],
        ] as const).map(([label, value, href]) => (
          <Card key={label}>
            <CardHeader>
              <CardDescription>{label}</CardDescription>
              <CardTitle className="text-3xl">{value}</CardTitle>
            </CardHeader>
            <CardContent>
              <Link href={href} className="text-sm text-blue-600 hover:underline">
                Ver →
              </Link>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="mt-6 grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Precisam de informação</CardTitle>
            <CardDescription>Complete e reenvie para análise técnica</CardDescription>
          </CardHeader>
          <CardContent>
            {needsInfoDemands.length === 0 ? (
              <p className="text-sm text-gray-500 dark:text-gray-400">Nenhuma demanda aguardando complemento.</p>
            ) : (
              <ul className="space-y-2 text-sm">
                {needsInfoDemands.map((d) => (
                  <DemandRow key={d.id} d={d} showActions />
                ))}
              </ul>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Rascunhos</CardTitle>
            <CardDescription>Edite e envie quando estiver pronto</CardDescription>
          </CardHeader>
          <CardContent>
            {draftDemands.length === 0 ? (
              <p className="text-sm text-gray-500 dark:text-gray-400">Nenhum rascunho.</p>
            ) : (
              <ul className="space-y-2 text-sm">
                {draftDemands.map((d) => (
                  <DemandRow key={d.id} d={d} showActions />
                ))}
              </ul>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="mt-6 grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Minhas demandas recentes</CardTitle>
            <CardDescription>{stats.total} no total</CardDescription>
          </CardHeader>
          <CardContent>
            {demands.length === 0 ? (
              <p className="text-sm text-gray-500 dark:text-gray-400">
                Nenhuma demanda ainda.{' '}
                <Link href="/demands/create" className="text-blue-600 hover:underline">
                  Criar a primeira →
                </Link>
              </p>
            ) : (
              <ul className="space-y-2 text-sm">
                {demands.map((d) => (
                  <DemandRow key={d.id} d={d} />
                ))}
              </ul>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Oportunidades abertas</CardTitle>
            <CardDescription>Funil — {brl(stats.pipeline_estimated)} estimado</CardDescription>
          </CardHeader>
          <CardContent>
            {openOpportunities.length === 0 ? (
              <p className="text-sm text-gray-500 dark:text-gray-400">
                Nenhuma oportunidade aberta.{' '}
                <Link href="/opportunities/create" className="text-blue-600 hover:underline">
                  Criar a primeira →
                </Link>
              </p>
            ) : (
              <ul className="space-y-2 text-sm">
                {openOpportunities.map((o) => (
                  <li key={o.id} className="flex items-center justify-between border-b pb-2 last:border-0">
                    <div>
                      <Link href={`/opportunities/${o.id}`} className="font-medium text-blue-600 hover:underline">
                        {o.title}
                      </Link>
                      <p className="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <span>{o.client?.name ?? 'Sem cliente'}</span>
                        <StatusBadge status={o.status} />
                        {o.estimated_value !== null && o.estimated_value !== undefined && (
                          <span>{brl(Number(o.estimated_value))}</span>
                        )}
                      </p>
                    </div>
                  </li>
                ))}
              </ul>
            )}
            <Link href="/opportunities" className="mt-3 inline-block text-sm text-blue-600 hover:underline">
              Ver todas as oportunidades →
            </Link>
          </CardContent>
        </Card>
      </div>

      <Card className="mt-6">
        <CardHeader>
          <CardTitle>Financeiro resumido</CardTitle>
          <CardDescription>Seus clientes — contratado vs recebido (veja o relatório para detalhes)</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid gap-4 sm:grid-cols-3 text-sm">
            <div>
              <p className="text-gray-500 dark:text-gray-400">Receita a receber</p>
              <p className="text-xl font-bold">{brl(financeSummary.receita_pendente)}</p>
            </div>
            <div>
              <p className="text-gray-500 dark:text-gray-400">Receita vencida</p>
              <p className={`text-xl font-bold ${financeSummary.receita_vencida > 0 ? 'text-red-600 dark:text-red-400' : ''}`}>{brl(financeSummary.receita_vencida)}</p>
            </div>
            <div>
              <p className="text-gray-500 dark:text-gray-400">Recebido no mês</p>
              <p className="text-xl font-bold text-green-600 dark:text-green-400">{brl(financeSummary.receita_recebida_mes)}</p>
            </div>
          </div>
          <Link href="/finance/report" className="mt-3 inline-block text-sm text-blue-600 hover:underline">
            Ver relatório financeiro →
          </Link>
        </CardContent>
      </Card>
    </>
  );
}
