<?php

namespace App\Models;

use Database\Factories\WebsiteHealthCheckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['domain_id', 'environment_id', 'website_id', 'trigger_source', 'scheduled_run_id', 'cycle_id', 'state_at_check', 'target_host', 'http_status', 'ok', 'latency_ms', 'ssl_valid', 'ssl_expires_at', 'error_code', 'checked_at'])]
class WebsiteHealthCheck extends Model
{
    /** @use HasFactory<WebsiteHealthCheckFactory> */
    use HasFactory, HasUuids;

    protected $casts = [
        'ssl_expires_at' => 'datetime',
        'checked_at' => 'datetime',
        'http_status' => 'integer',
        'ok' => 'boolean',
        'latency_ms' => 'integer',
        'ssl_valid' => 'boolean',
        'scheduled_run_id' => 'integer',
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
