<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskBlocked extends Notification
{
    use Queueable;

    public function __construct(public Task $task) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Tarefa bloqueada',
            'description' => $this->task->title,
            'url' => route('tasks.show', $this->task),
            'task_id' => $this->task->id,
        ];
    }
}
