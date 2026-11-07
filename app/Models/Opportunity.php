<?php

namespace App\Models;

use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['title', 'client_id', 'contact_name', 'responsible_id', 'service_type', 'description', 'estimated_value', 'negotiated_value', 'opened_at', 'expected_close_at', 'status', 'notes', 'created_by'])]
class Opportunity extends Model
{
    /** @use HasFactory<OpportunityFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $casts = [
        'estimated_value' => 'decimal:2',
        'negotiated_value' => 'decimal:2',
        'opened_at' => 'date',
        'expected_close_at' => 'date',
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

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(OpportunityActivity::class);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['ganha', 'perdida', 'cancelada']);
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['ganha', 'perdida', 'cancelada'], true);
    }
}
