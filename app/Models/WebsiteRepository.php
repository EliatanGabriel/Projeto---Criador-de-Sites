<?php

namespace App\Models;

use Database\Factories\WebsiteRepositoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['website_id', 'environment_id', 'provider', 'name', 'url', 'default_branch', 'github_full_name', 'last_commit_sha', 'last_commit_at', 'last_synced_at', 'last_sync_attempt_at', 'last_sync_status', 'last_sync_error', 'open_pull_requests_count', 'notes'])]
class WebsiteRepository extends Model
{
    /** @use HasFactory<WebsiteRepositoryFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $casts = [
        'last_commit_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'last_sync_attempt_at' => 'datetime',
        'open_pull_requests_count' => 'integer',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function uniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(WebsiteEnvironment::class, 'environment_id');
    }

    public function events(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WebsiteRepositoryEvent::class, 'repository_id');
    }
}
