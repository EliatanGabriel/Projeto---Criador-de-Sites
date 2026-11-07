<?php

namespace App\Models;

use Database\Factories\ProjectTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'description', 'category', 'version', 'is_active', 'created_by', 'updated_by'])]
class ProjectTemplate extends Model
{
    /** @use HasFactory<ProjectTemplateFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $casts = ['is_active' => 'boolean'];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function uniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectTemplatePhase::class, 'template_id')->orderBy('order');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
