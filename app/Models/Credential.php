<?php

namespace App\Models;

use Database\Factories\CredentialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['website_id', 'hosting_id', 'domain_id', 'environment_id', 'type', 'label', 'username', 'notes', 'last_rotated_at'])]
class Credential extends Model
{
    /** @use HasFactory<CredentialFactory> */
    use HasFactory, HasUuids;

    protected $hidden = ['secret_encrypted'];

    protected $casts = [
        'secret_encrypted' => 'encrypted',
        'last_rotated_at' => 'datetime',
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

    public function hosting(): BelongsTo
    {
        return $this->belongsTo(Hosting::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(WebsiteDomain::class, 'domain_id');
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(WebsiteEnvironment::class, 'environment_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CredentialAccessLog::class);
    }
}
