<?php

namespace App\Models;

use Database\Factories\WebsiteRepositoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['website_id', 'environment_id', 'provider', 'name', 'url', 'default_branch', 'notes'])]
class WebsiteRepository extends Model
{
    /** @use HasFactory<WebsiteRepositoryFactory> */
    use HasFactory, HasUuids, SoftDeletes;

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
}
