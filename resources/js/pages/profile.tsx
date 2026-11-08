import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface Profile {
  id: string;
  name: string;
  email: string;
  is_active: boolean;
  email_verified_at: string | null;
  last_login_at: string | null;
  created_at: string | null;
  roles: string[];
  permissions: string[];
}

const ROLE_LABELS: Record<string, string> = {
  OWNER: 'Administrador (OWNER)',
  COMMERCIAL: 'Comercial',
  DEVELOPER: 'Developer',
};

const PANEL_LABELS: Record<string, string> = {
  OWNER: 'Painel Admin',
  COMMERCIAL: 'Painel Comercial',
  DEVELOPER: 'Painel Developer',
};

const date = (v: string | null) => (v ? new Date(v).toLocaleString('pt-BR') : '—');

export default function Profile({ profile }: { profile: Profile }) {
  const panels = profile.roles
    .flatMap((r) => (r === 'OWNER' ? ['Painel Admin', 'Painel Comercial', 'Painel Developer'] : [PANEL_LABELS[r]].filter(Boolean)))
    .filter((v, i, a) => a.indexOf(v) === i);
  return (
    <AuthenticatedLayout>
      <Head title="Meu Perfil" />
      <div className="mb-6">
        <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Meu Perfil</h2>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Informações do usuário logado.</p>
      </div>
      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <div className="flex items-center gap-4">
              <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-blue-600 text-2xl font-bold text-white">
                {profile.name?.charAt(0).toUpperCase() ?? '?'}
              </div>
              <div>
                <CardTitle className="text-xl">{profile.name}</CardTitle>
                <CardDescription>{profile.email}</CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <dl className="space-y-3 text-sm">
              <div className="flex justify-between gap-4">
                <dt className="text-gray-500 dark:text-gray-400">Status</dt>
                <dd className="font-medium">{profile.is_active ? 'Ativo' : 'Inativo'}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt className="text-gray-500 dark:text-gray-400">E-mail verificado em</dt>
                <dd className="font-medium">{date(profile.email_verified_at)}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt className="text-gray-500 dark:text-gray-400">Último login</dt>
                <dd className="font-medium">{date(profile.last_login_at)}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt className="text-gray-500 dark:text-gray-400">Conta criada em</dt>
                <dd className="font-medium">{date(profile.created_at)}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt className="text-gray-500 dark:text-gray-400">ID</dt>
                <dd className="max-w-[60%] truncate font-mono text-xs">{profile.id}</dd>
              </div>
            </dl>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Perfis e acessos</CardTitle>
            <CardDescription>Perfis atribuídos a este usuário</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-4 text-sm">
              <div>
                <p className="font-medium">Perfis</p>
                <ul className="mt-1 space-y-1">
                  {profile.roles.length === 0 && <li className="text-gray-500">Nenhum perfil atribuído.</li>}
                  {profile.roles.map((r) => (
                    <li key={r} className="rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300 sm:inline-block sm:mr-1">
                      {ROLE_LABELS[r] ?? r}
                    </li>
                  ))}
                </ul>
              </div>
              <div>
                <p className="font-medium">Painéis disponíveis</p>
                <p className="mt-1 text-gray-600 dark:text-gray-400">{panels.length > 0 ? panels.join(', ') : '—'}</p>
              </div>
              <div>
                <p className="font-medium">Permissões ({profile.permissions.length})</p>
                {profile.permissions.length === 0 ? (
                  <p className="mt-1 text-gray-500">Nenhuma permissão.</p>
                ) : (
                  <ul className="mt-2 flex flex-wrap gap-1">
                    {profile.permissions.map((p) => (
                      <li key={p} className="rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                        {p}
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </AuthenticatedLayout>
  );
}
