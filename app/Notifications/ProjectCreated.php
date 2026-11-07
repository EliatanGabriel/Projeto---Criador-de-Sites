<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectCreated extends Notification
{
    use Queueable;

    public function __construct(public Project $project) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Projeto criado',
            'description' => $this->project->title,
            'url' => route('projects.show', $this->project),
            'project_id' => $this->project->id,
        ];
    }
}
