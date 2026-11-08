import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export default function HostingsShow({ hosting }: { hosting: any }) {
  return (
    <AuthenticatedLayout>
      <Head title={hosting.name} />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">{hosting.name}</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{hosting.provider} • {hosting.status}</p>
        </div>
        <Link href={`/hostings/${hosting.id}/edit`}>
          <Button>Editar</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Detalhes</CardTitle>
        </CardHeader>
        <CardContent className="space-y-2 text-sm">
          <p><span className="font-medium">Provedor:</span> {hosting.provider}</p>
          <p><span className="font-medium">Plano:</span> {hosting.plan ?? '—'}</p>
          <p><span className="font-medium">Conta:</span> {hosting.account_identifier ?? '—'}</p>
          <p><span className="font-medium">Observações:</span> {hosting.notes ?? '—'}</p>
        </CardContent>
      </Card>

      <Card className="mt-4">
        <CardHeader>
          <CardTitle>Ambientes vinculados ({hosting.environments?.length ?? 0})</CardTitle>
        </CardHeader>
        <CardContent>
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b text-left text-gray-600 dark:text-gray-400">
                <th className="pb-2">Site</th>
                <th className="pb-2">Ambiente</th>
                <th className="pb-2">Tipo</th>
                <th className="pb-2">URL</th>
              </tr>
            </thead>
            <tbody>
              {(hosting.environments ?? []).map((e: any) => (
                <tr key={e.id} className="border-b last:border-0">
                  <td className="py-2 font-medium">{e.website?.name ?? '—'}</td>
                  <td className="py-2">{e.name}</td>
                  <td className="py-2">{e.type}</td>
                  <td className="py-2">{e.url ?? '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </CardContent>
      </Card>
    </AuthenticatedLayout>
  );
}
