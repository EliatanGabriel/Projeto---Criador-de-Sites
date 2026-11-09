import { Head, Link, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Pagination from '@/components/pagination';
import type { Paginator } from '@/types';

interface TeamUser {
  id: string;
  name: string;
  email: string;
  is_active: boolean;
  last_login_at?: string | null;
  roles: { name: string }[];
  created_at: string;
}

export default function TeamIndex({ users }: { users: Paginator<TeamUser> }) {
  const toggleForm = useForm({});
  function toggle(id: string, name: string, active: boolean) {
    if (!confirm(`${active ? 'Desativar' : 'Ativar'} o usuário ${name}? ${active ? 'Sessões ativas serão encerradas.' : ''}`)) return;
    toggleForm.post(`/team/${id}/toggle-active`);
  }

  return (
    <>
      <Head title="Equipe" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Equipe</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{users.total} usuários cadastrados</p>
        </div>
        <Link href="/team/create">
          <Button>Novo usuário</Button>
        </Link>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Usuários internos</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-gray-600 dark:text-gray-400">
                  <th className="pb-2">Nome</th>
                  <th className="pb-2">E-mail</th>
                  <th className="pb-2">Perfil</th>
                  <th className="pb-2">Status</th>
                  <th className="pb-2">Último acesso</th>
                  <th className="pb-2 text-right">Ações</th>
                </tr>
              </thead>
              <tbody>
                {users.data.length === 0 && (
                  <tr>
                    <td colSpan={6} className="py-8 text-center text-gray-500 dark:text-gray-400">
                      Nenhum usuário encontrado.
                    </td>
                  </tr>
                )}
                {users.data.map((u) => (
                  <tr key={u.id} className="border-b last:border-0">
                    <td className="py-3 font-medium">{u.name}</td>
                    <td className="py-3">{u.email}</td>
                    <td className="py-3">{u.roles.map((r) => r.name).join(', ')}</td>
                    <td className="py-3">
                      <span className={u.is_active ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700' : 'rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700'}>
                        {u.is_active ? 'Ativo' : 'Inativo'}
                      </span>
                    </td>
                    <td className="py-3 text-gray-500 dark:text-gray-400">{u.last_login_at ? new Date(u.last_login_at).toLocaleString('pt-BR') : '—'}</td>
                    <td className="py-3 text-right">
                      <Link href={`/team/${u.id}/edit`} className="mr-3 text-blue-600 hover:underline">
                        Editar
                      </Link>
                      <button onClick={() => toggle(u.id, u.name, u.is_active)} className={u.is_active ? 'text-red-600 hover:underline' : 'text-emerald-600 hover:underline'}>
                        {u.is_active ? 'Desativar' : 'Ativar'}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <div className="mt-4 flex justify-center">
            <Pagination links={users.links} />
          </div>
        </CardContent>
      </Card>
    </>
  );
}
