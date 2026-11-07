<?php

namespace App\Http\Controllers;

use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = request()->user()->notifications()->latest()->paginate(20);

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
            'unreadCount' => request()->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(DatabaseNotification $notification)
    {
        if ((string) $notification->notifiable_id !== (string) request()->user()->id) {
            abort(403);
        }
        $notification->markAsRead();
        return redirect()->route('notifications.index')->with('success', 'Notificação marcada como lida.');
    }

    public function markAllRead()
    {
        request()->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);
        return redirect()->route('notifications.index')->with('success', 'Todas as notificações foram marcadas como lidas.');
    }
}
