<?php

namespace App\Notifications;

use App\Models\Demand;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DemandSubmitted extends Notification
{
    use Queueable;

    public function __construct(public Demand $demand) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Nova demanda enviada para análise',
            'description' => $this->demand->title,
            'url' => route('demands.show', $this->demand),
            'demand_id' => $this->demand->id,
        ];
    }
}
