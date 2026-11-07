<?php

namespace App\Models;

use Database\Factories\WebsitePublicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['website_id', 'environment_id', 'version', 'published_at', 'responsible_id', 'description', 'commit_reference', 'result', 'source', 'github_deployment_id', 'notes'])]
class WebsitePublication extends Model
{
    /** @use HasFactory<WebsitePublicationFactory> */
    use HasFactory, HasUuids;

    protected $casts = ['published_at' => 'datetime'];

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

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }
}
