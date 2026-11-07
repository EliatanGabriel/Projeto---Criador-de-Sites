<?php

namespace App\Domain\Demands\Actions;

use App\Enums\DemandStatus;
use App\Models\Demand;
use Illuminate\Support\Facades\DB;

class TransitionDemandAction
{
    public function __invoke(Demand $demand, DemandStatus $to, \App\Models\User $user, ?string $note = null, array $metadata = []): Demand
    {
        $from = DemandStatus::tryFrom($demand->status) ?? DemandStatus::Draft;
        if (! $from->canTransitionTo($to)) {
            abort(422, "Transição inválida: {$from->value} → {$to->value}.");
        }

        DB::transaction(function () use ($demand, $from, $to, $user, $note, $metadata) {
            $now = now();
            $demand->status = $to->value;

            match ($to) {
                DemandStatus::Submitted => $demand->submitted_at = $now,
                DemandStatus::UnderReview => $demand->reviewed_at = $now,
                DemandStatus::Approved => $demand->approved_at = $now,
                DemandStatus::Rejected => $demand->rejected_at = $now,
                default => null,
            };

            if ($to === DemandStatus::UnderReview) {
                $demand->technical_owner_id = $user->id;
            }
            if (in_array($to, [DemandStatus::NeedsInformation, DemandStatus::Approved, DemandStatus::Rejected])) {
                $demand->technical_owner_id = $demand->technical_owner_id ?? $user->id;
            }

            $demand->save();

            $demand->histories()->create([
                'user_id' => $user->id,
                'event' => 'status_changed',
                'description' => $note ?: sprintf('Status alterado de %s para %s.', $from->label(), $to->label()),
                'from_status' => $from->value,
                'to_status' => $to->value,
                'metadata' => $metadata ?: null,
            ]);
        });

        return $demand->fresh(['briefing', 'attachments', 'histories.user:id,name']);
    }
}
