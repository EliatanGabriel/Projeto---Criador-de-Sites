<?php

namespace App\Notifications;

use App\Models\Demand;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DemandStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public Demand $demand, public string $message) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->message,
            'description' => $this->demand->title,
            'url' => route('demands.show', $this->demand),
            'demand_id' => $this->demand->id,
        ];
    }
}
