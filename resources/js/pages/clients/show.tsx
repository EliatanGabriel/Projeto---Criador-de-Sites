import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import type { Client, Opportunity } from '@/types';

interface ShowProps {
  client: Client & { creator?: { name: string } | null; opportunities?: Opportunity[] };
}

export default function ClientsShow({ client }: ShowProps) {
  return (
    <AuthenticatedLayout>
      <Head title={client.name} />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">{client.name}</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {client.type === 'pf' ? 'Pessoa física' : 'Pessoa jurídica'} • {client.status === 'ativo' ? 'Ativo' : 'Inativo'} • Cadastro em {new Date(client.created_at).toLocaleDateString('pt-BR')}
          </p>
        </div>
        <Link href={`/clients/${client.id}/edit`}>
          <Button>Editar</Button>
        </Link>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Dados cadastrais</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            <p><span className="font-medium">Documento:</span> {client.document ?? '—'}</p>
            <p><span className="font-medium">E-mail:</span> {client.email ?? '—'}</p>
            <p><span className="font-medium">Telefone:</span> {client.phone ?? '—'}</p>
            <p><span className="font-medium">Contato:</span> {client.contact_name ?? '—'}</p>
            <p><span className="font-medium">Endereço:</span> {client.address ?? '—'}, {client.city ?? ''} {client.state ?? ''} {client.zip ?? ''}</p>
            <p><span className="font-medium">Observações:</span> {client.notes ?? '—'}</p>
            <p><span className="font-medium">Cadastrado por:</span> {client.creator?.name ?? '—'}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Oportunidades</CardTitle>
            <CardDescription>{client.opportunities?.length ?? 0} oportunidades vinculadas</CardDescription>
          </CardHeader>
          <CardContent>
            {(client.opportunities?.length ?? 0) === 0 ? (
              <p className="text-sm text-gray-500 dark:text-gray-400">Nenhuma oportunidade registrada para este cliente.</p>
            ) : (
              <ul className="space-y-3">
                {client.opportunities!.map((o) => (
                  <li key={o.id} className="flex items-center justify-between border-b pb-2 last:border-0">
                    <div>
                      <Link href={`/opportunities/${o.id}`} className="font-medium text-blue-600 hover:underline">
                        {o.title}
                      </Link>
                      <p className="text-xs text-gray-500 dark:text-gray-400">{new Date(o.created_at).toLocaleDateString('pt-BR')} • {o.status}</p>
                    </div>
                    <span className="text-sm font-medium">{o.estimated_value ? Number(o.estimated_value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '—'}</span>
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
