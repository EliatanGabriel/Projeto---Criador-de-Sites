<?php

namespace App\Models;

use Database\Factories\WebsiteDomainHealthStateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['domain_id', 'website_id', 'environment_id', 'state', 'cycle_id', 'cycle_number', 'cycle_started_at', 'consecutive_failures', 'last_applied_run_id', 'last_checked_at', 'last_ok_at', 'last_recovered_at'])]
class WebsiteDomainHealthState extends Model
{
    /** @use HasFactory<WebsiteDomainHealthStateFactory> */
    use HasFactory, HasUuids;

    protected $casts = [
        'cycle_started_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'last_ok_at' => 'datetime',
        'last_recovered_at' => 'datetime',
        'consecutive_failures' => 'integer',
        'last_applied_run_id' => 'integer',
        'cycle_number' => 'integer',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function uniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(WebsiteDomain::class, 'domain_id');
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
