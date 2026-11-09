import { Head, Link, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

export default function TemplatesShow({ template }: { template: any }) {
  const toggle = useForm({});

  return (
    <>
      <Head title={template.name} />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">{template.name}</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {template.category} • v{template.version} • {template.is_active ? 'Ativo' : 'Inativo'}
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="secondary" onClick={() => toggle.post(`/templates/${template.id}/toggle-active`)} disabled={toggle.processing}>
            {template.is_active ? 'Inativar' : 'Ativar'}
          </Button>
          <Link href={`/templates/${template.id}/edit`}>
            <Button>Editar</Button>
          </Link>
        </div>
      </div>

      <Card className="max-w-4xl">
        <CardHeader>
          <CardTitle>Descrição</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-gray-700 dark:text-gray-300">{template.description ?? '—'}</p>
        </CardContent>
      </Card>

      {(template.phases ?? []).map((p: any) => (
        <Card key={p.id} className="mt-4 max-w-4xl">
          <CardHeader>
            <CardTitle>
              Etapa {p.order} — {p.name}
            </CardTitle>
            <CardDescription>{p.description ?? ''}</CardDescription>
          </CardHeader>
          <CardContent>
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-gray-600 dark:text-gray-400">
                  <th className="pb-2">Tarefa</th>
                  <th className="pb-2">Prioridade</th>
                  <th className="pb-2">Sugerido</th>
                  <th className="pb-2">Prazo</th>
                  <th className="pb-2">Obrigatória</th>
                </tr>
              </thead>
              <tbody>
                {(p.tasks ?? []).map((t: any) => (
                  <tr key={t.id} className="border-b last:border-0">
                    <td className="py-2">
                      <p className="font-medium">{t.title}</p>
                      {t.description && <p className="text-xs text-gray-500 dark:text-gray-400">{t.description}</p>}
                    </td>
                    <td className="py-2">{t.priority}</td>
                    <td className="py-2">{t.suggested_role ?? '—'}</td>
                    <td className="py-2">{t.estimated_days != null ? `${t.estimated_days}d` : '—'}</td>
                    <td className="py-2">{t.is_required ? 'Sim' : 'Não'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </CardContent>
        </Card>
      ))}
    </>
  );
}
