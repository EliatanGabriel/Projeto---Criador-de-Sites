<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['phase_id', 'title', 'description', 'priority', 'suggested_role', 'estimated_days', 'order', 'is_required'])]
class ProjectTemplateTask extends Model
{
    use HasUuids;

    protected $casts = ['is_required' => 'boolean'];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function uniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectTemplatePhase::class, 'phase_id');
    }
}
