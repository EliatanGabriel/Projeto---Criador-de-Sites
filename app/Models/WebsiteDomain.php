<?php

namespace App\Models;

use Database\Factories\WebsiteDomainFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['website_id', 'domain', 'is_primary', 'registrar', 'registered_at', 'expires_at', 'status', 'dns_notes'])]
class WebsiteDomain extends Model
{
    /** @use HasFactory<WebsiteDomainFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $casts = [
        'is_primary' => 'boolean',
        'registered_at' => 'date',
        'expires_at' => 'date',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function uniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }

    protected static function booted(): void
    {
        static::saving(function (WebsiteDomain $domain) {
            $domain->domain = mb_strtolower(trim((string) $domain->domain));
        });
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class, 'domain_id');
    }
}
