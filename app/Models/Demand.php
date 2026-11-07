<?php

namespace App\Models;

use Database\Factories\DemandFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'client_id', 'opportunity_id', 'commercial_owner_id', 'service_type', 'description', 'contracted_scope', 'negotiated_value', 'contracted_at', 'desired_deadline', 'priority', 'status', 'internal_notes', 'technical_owner_id', 'submitted_at', 'reviewed_at', 'approved_at', 'rejected_at', 'created_by'])]
class Demand extends Model
{
    /** @use HasFactory<DemandFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $casts = [
        'negotiated_value' => 'decimal:2',
        'contracted_at' => 'date',
        'desired_deadline' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function uniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function commercialOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'commercial_owner_id');
    }

    public function technicalOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technical_owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function briefing(): HasOne
    {
        return $this->hasOne(DemandBriefing::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DemandAttachment::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DemandHistory::class);
    }

    public function isEditableContent(): bool
    {
        return in_array($this->status, ['draft', 'needs_information'], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['approved', 'rejected', 'cancelled'], true);
    }
}
