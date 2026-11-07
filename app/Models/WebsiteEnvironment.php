<?php

namespace App\Models;

use Database\Factories\WebsiteEnvironmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['website_id', 'hosting_id', 'name', 'type', 'url', 'status', 'notes'])]
class WebsiteEnvironment extends Model
{
    /** @use HasFactory<WebsiteEnvironmentFactory> */
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

    public function hosting(): BelongsTo
    {
        return $this->belongsTo(Hosting::class);
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(WebsiteRepository::class, 'environment_id');
    }

    public function publications(): HasMany
    {
        return $this->hasMany(WebsitePublication::class, 'environment_id');
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class, 'environment_id');
    }
}
