<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['demand_id', 'client_id', 'title', 'description', 'scope', 'technical_owner_id', 'started_at', 'planned_deadline', 'completed_at', 'status', 'priority', 'contracted_value', 'internal_notes', 'blocked_reason', 'converted_by', 'converted_at', 'template_id', 'template_version', 'template_snapshot'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $casts = [
        'started_at' => 'date',
        'planned_deadline' => 'date',
        'completed_at' => 'date',
        'converted_at' => 'datetime',
        'contracted_value' => 'decimal:2',
        'template_snapshot' => 'array',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function uniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }

    public function demand(): BelongsTo
    {
        return $this->belongsTo(Demand::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplate::class, 'template_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function technicalOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technical_owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProjectHistory::class);
    }

    public function progressData(): array
    {
        $tasks = $this->relationLoaded('tasks') ? $this->getRelation('tasks') : $this->tasks()->get(['status']);
        $total = $tasks->count();
        $done = $tasks->where('status', 'done')->count();
        $byStatus = $tasks->groupBy('status')->map->count()->toArray();

        return [
            'total' => $total,
            'done' => $done,
            'percentage' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
            'todo' => $byStatus['todo'] ?? 0,
            'in_progress' => $byStatus['in_progress'] ?? 0,
            'in_review' => $byStatus['in_review'] ?? 0,
            'blocked' => $byStatus['blocked'] ?? 0,
            'cancelled' => $byStatus['cancelled'] ?? 0,
        ];
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['completed', 'cancelled'], true);
    }
}
