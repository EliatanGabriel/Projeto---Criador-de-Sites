import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Pagination from '@/components/pagination';
import type { Paginator } from '@/types';

const SEVERITY_COLOR: Record<string, string> = {
  info: 'bg-blue-100 text-blue-700',
  warning: 'bg-amber-100 text-amber-700',
  critical: 'bg-red-100 text-red-700',
};

export default function AlertsIndex({ alerts, filters, summary, websites, users }: { alerts: Paginator<any>; filters: Record<string, string>; summary: { open: number; info: number; warning: number; critical: number }; websites: { id: string; name: string }[]; users: { id: string; name: string }[] }) {
  const [severity, setSeverity] = useState(filters.severity ?? '');
  const [status, setStatus] = useState(filters.status ?? '');
  const [websiteId, setWebsiteId] = useState(filters.website_id ?? '');

  function applyFilters(e: React.FormEvent) {
    e.preventDefault();
    const p = new URLSearchParams();
    if (severity) p.set('severity', severity);
    if (status) p.set('status', status);
    if (websiteId) p.set('website_id', websiteId);
    window.location.href = `/alerts?${p.toString()}`;
  }

  return (
    <AuthenticatedLayout>
      <Head title="Alertas" />
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Central de alertas</h2>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Abertos: {summary.open} • Info: {summary.info} • Warning: {summary.warning} • Critical: {summary.critical}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Filtros</CardTitle>
        </CardHeader>
        <CardContent>
          <form onSubmit={applyFilters} className="flex flex-wrap gap-3">
            <select value={severity} onChange={(e) => setSeverity(e.target.value)} className="rounded-md border px-3 py-2 text-sm">
              <option value="">Todas severidades</option>
              <option value="info">Info</option>
              <option value="warning">Warning</option>
              <option value="critical">Critical</option>
            </select>
            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border px-3 py-2 text-sm">
              <option value="">Todos os estados</option>
              <option value="open">Aberto</option>
              <option value="resolved">Resolvido</option>
              <option value="dismissed">Dispensado</option>
            </select>
            <select value={websiteId} onChange={(e) => setWebsiteId(e.target.value)} className="rounded-md border px-3 py-2 text-sm">
              <option value="">Todos os sites</option>
              {websites.map((w) => (
                <option key={w.id} value={w.id}>
                  {w.name}
                </option>
              ))}
            </select>
            <Button type="submit" variant="secondary">
              Filtrar
            </Button>
          </form>
        </CardContent>
      </Card>

      <Card className="mt-4">
        <CardContent className="pt-6">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b text-left text-gray-600 dark:text-gray-400">
                <th className="pb-2">Título</th>
                <th className="pb-2">Site</th>
                <th className="pb-2">Tipo</th>
                <th className="pb-2">Severidade</th>
                <th className="pb-2">Vence</th>
                <th className="pb-2">Estado</th>
                <th className="pb-2 text-right">Ações</th>
              </tr>
            </thead>
            <tbody>
              {alerts.data.length === 0 && (
                <tr>
                  <td colSpan={7} className="py-8 text-center text-gray-500 dark:text-gray-400">
                    Nenhum alerta encontrado.
                  </td>
                </tr>
              )}
              {alerts.data.map((a: any) => (
                <tr key={a.id} className="border-b last:border-0">
                  <td className="py-3 font-medium">{a.title}</td>
                  <td className="py-3">{a.website?.name ?? '—'}</td>
                  <td className="py-3">{a.alert_type}</td>
                  <td className="py-3">
                    <span className={`rounded-full px-2 py-0.5 text-xs ${SEVERITY_COLOR[a.severity] ?? 'bg-gray-100 dark:bg-gray-800'}`}>{a.severity}</span>
                  </td>
                  <td className="py-3">{a.due_at ? new Date(a.due_at).toLocaleDateString('pt-BR') : '—'}</td>
                  <td className="py-3">{a.status}</td>
                  <td className="py-3 text-right">
                    <Link href={`/alerts/${a.id}`} className="text-blue-600 hover:underline">
                      Ver
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          <div className="mt-4 flex justify-center">
            <Pagination links={alerts.links} />
          </div>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
