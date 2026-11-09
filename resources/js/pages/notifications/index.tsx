import { Head, Link, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Pagination from '@/components/pagination';
import type { Paginator } from '@/types';

export default function NotificationsIndex({ notifications, unreadCount }: { notifications: Paginator<any>; unreadCount: number }) {
  const markAll = useForm({});
  const markOne = useForm({});

  return (
    <>
      <Head title="Notificações" />
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Notificações</h2>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{unreadCount} não lidas</p>
        </div>
        {unreadCount > 0 && (
          <Button variant="secondary" onClick={() => markAll.post('/notifications/read-all')} disabled={markAll.processing}>
            Marcar todas como lidas
          </Button>
        )}
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Central de notificações</CardTitle>
        </CardHeader>
        <CardContent>
          {notifications.data.length === 0 ? (
            <p className="text-sm text-gray-500 dark:text-gray-400">Nenhuma notificação.</p>
          ) : (
            <ul className="space-y-3">
              {notifications.data.map((n: any) => (
                <li key={n.id} className={`flex items-center justify-between rounded-md border p-3 ${n.read_at ? 'bg-white dark:bg-gray-900' : 'bg-blue-50'}`}>
                  <div>
                    <p className="font-medium">{n.data.title ?? n.type}</p>
                    <p className="text-sm text-gray-600 dark:text-gray-400">{n.data.description ?? ''}</p>
                    <p className="text-xs text-gray-500 dark:text-gray-400">{new Date(n.created_at).toLocaleString('pt-BR')}</p>
                  </div>
                  <div className="flex gap-2">
                    {n.data.url && (
                      <Link href={n.data.url} className="text-sm text-blue-600 hover:underline">
                        Abrir
                      </Link>
                    )}
                    {!n.read_at && (
                      <button
                        onClick={() => markOne.patch(`/notifications/${n.id}/read`)}
                        className="text-sm text-gray-600 dark:text-gray-400 hover:underline"
                      >
                        Marcar lida
                      </button>
                    )}
                  </div>
                </li>
              ))}
            </ul>
          )}
          <div className="mt-4 flex justify-center">
            <Pagination links={notifications.links} />
          </div>
        </CardContent>
      </Card>
    </>
  );
}
