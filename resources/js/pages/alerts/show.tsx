import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

export default function AlertsShow({ alert, isRead, canRead, canResolve, users }: { alert: any; isRead: boolean; canRead: boolean; canResolve: boolean; users: { id: string; name: string }[] }) {
  const [assignee, setAssignee] = useState(alert.assigned_to ?? '');
  const readForm = useForm({});
  const resolveForm = useForm({});
  const dismissForm = useForm({});
  const assignForm = useForm({ assigned_to: '' });

  const timeline = [...(alert.activities ?? [])].reverse();

  return (
    <>
      <Head title={alert.title} />
      <div className="mb-6">
        <Link href="/alerts" className="text-sm text-blue-600 hover:underline">
          ← Voltar à central
        </Link>
        <h2 className="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{alert.title}</h2>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          {alert.website?.name ?? '—'} • {alert.alert_type} • {alert.severity} • {alert.status}
        </p>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Detalhes</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            <p><span className="font-medium">Site:</span> {alert.website?.name ?? '—'}</p>
            <p><span className="font-medium">Fonte:</span> {alert.source_type ?? '—'} {alert.source_id ?? ''}</p>
            <p><span className="font-medium">Vence em:</span> {alert.due_at ? new Date(alert.due_at).toLocaleDateString('pt-BR') : '—'}</p>
            <p><span className="font-medium">Descrição:</span> {alert.description ?? '—'}</p>
            <p><span className="font-medium">Responsável:</span> {alert.assignee?.name ?? '—'}</p>
            <p><span className="font-medium">Resolvido em:</span> {alert.resolved_at ? new Date(alert.resolved_at).toLocaleString('pt-BR') : '—'}</p>
            <p><span className="font-medium">Lido por você:</span> {isRead ? 'Sim' : 'Não'}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Ações</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4 text-sm">
            {canRead && !isRead && (
              <Button onClick={() => readForm.post(`/alerts/${alert.id}/read`)} disabled={readForm.processing}>
                Marcar como lido
              </Button>
            )}
            {canResolve && alert.status === 'open' && (
              <div className="flex gap-2">
                <Button onClick={() => resolveForm.post(`/alerts/${alert.id}/resolve`)} disabled={resolveForm.processing}>
                  Resolver
                </Button>
                <Button variant="outline" onClick={() => dismissForm.post(`/alerts/${alert.id}/dismiss`)} disabled={dismissForm.processing}>
                  Dispensar
                </Button>
              </div>
            )}
            <div className="space-y-2 border-t pt-3">
              <Label htmlFor="assigned_to">Atribuir responsável</Label>
              <div className="flex gap-2">
                <select id="assigned_to" value={assignee} onChange={(e) => { setAssignee(e.target.value); assignForm.setData('assigned_to', e.target.value); }} className="flex-1 rounded-md border px-3 py-2 text-sm">
                  <option value="">— Sem responsável —</option>
                  {users.map((u) => (
                    <option key={u.id} value={u.id}>
                      {u.name}
                    </option>
                  ))}
                </select>
                <Button variant="secondary" onClick={() => assignForm.patch(`/alerts/${alert.id}/assign`)} disabled={assignForm.processing}>
                  Atribuir
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Histórico</CardTitle>
          </CardHeader>
          <CardContent>
            {timeline.length === 0 ? (
              <p className="text-sm text-gray-500 dark:text-gray-400">Sem histórico.</p>
            ) : (
              <ul className="space-y-2 text-sm">
                {timeline.map((h: any) => (
                  <li key={h.id} className="border-b pb-2">
                    <p className="font-medium">{h.event}</p>
                    {h.description && <p className="text-gray-600 dark:text-gray-400">{h.description}</p>}
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                      {h.user?.name ?? 'Sistema'} • {new Date(h.created_at).toLocaleString('pt-BR')}
                    </p>
                  </li>
                ))}
              </ul>
            )}
          </CardContent>
        </Card>
      </div>
    </>
  );
}
