<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['repository_id', 'website_id', 'delivery_id', 'event_type', 'payload_summary', 'github_deployment_id', 'commit_sha', 'ref', 'outcome'])]
class WebsiteRepositoryEvent extends Model
{
    use HasUuids;

    protected $casts = [
        'payload_summary' => 'array',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function uniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(WebsiteRepository::class);
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }
}
