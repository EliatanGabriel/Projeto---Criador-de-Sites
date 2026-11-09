import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function TasksShow({ task }: { task: any }) {
  const [comment, setComment] = useState('');
  const commentForm = useForm({ comment: '' });

  const timeline = [...(task.histories ?? [])].reverse();

  return (
    <>
      <Head title={task.title} />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">{task.title}</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {task.project?.title ?? '—'} • {task.status} • {task.priority}
          </p>
        </div>
        <Link href={`/tasks/${task.id}/edit`}>
          <Button>Editar</Button>
        </Link>
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Detalhes</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2 text-sm">
            <p><span className="font-medium">Projeto:</span> <Link href={`/projects/${task.project_id}`} className="text-blue-600 hover:underline">{task.project?.title ?? '—'}</Link></p>
            <p><span className="font-medium">Responsável:</span> {task.assignee?.name ?? '—'}</p>
            <p><span className="font-medium">Criador:</span> {task.creator?.name ?? '—'}</p>
            <p><span className="font-medium">Início:</span> {task.started_at ? new Date(task.started_at).toLocaleDateString('pt-BR') : '—'}</p>
            <p><span className="font-medium">Prazo:</span> {task.due_at ? new Date(task.due_at).toLocaleDateString('pt-BR') : '—'}</p>
            <p><span className="font-medium">Concluída em:</span> {task.completed_at ? new Date(task.completed_at).toLocaleString('pt-BR') : '—'}</p>
            <p><span className="font-medium">Descrição:</span> {task.description ?? '—'}</p>
            {task.blocked_reason && <p><span className="font-medium">Bloqueio:</span> {task.blocked_reason}</p>}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Comentários</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="mb-4 flex gap-2">
              <Input value={comment} onChange={(e) => { setComment(e.target.value); commentForm.setData('comment', e.target.value); }} placeholder="Adicionar comentário..." />
              <Button onClick={() => commentForm.post(`/tasks/${task.id}/comments`)} disabled={commentForm.processing || !comment}>
                Enviar
              </Button>
            </div>
            {(task.comments?.length ?? 0) === 0 ? (
              <p className="text-sm text-gray-500 dark:text-gray-400">Nenhum comentário.</p>
            ) : (
              <ul className="space-y-3 text-sm">
                {[...task.comments].reverse().map((c: any) => (
                  <li key={c.id} className="border-b pb-2">
                    <p className="font-medium">{c.user?.name ?? 'Usuário'}</p>
                    <p>{c.comment}</p>
                    <p className="text-xs text-gray-500 dark:text-gray-400">{new Date(c.created_at).toLocaleString('pt-BR')}</p>
                  </li>
                ))}
              </ul>
            )}
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
