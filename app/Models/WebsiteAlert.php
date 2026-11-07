<?php

namespace App\Models;

use Database\Factories\WebsiteAlertFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['website_id', 'alert_type', 'severity', 'source_type', 'source_id', 'title', 'description', 'due_at', 'fingerprint', 'assigned_to', 'status', 'resolved_at', 'resolved_by', 'dismissed_at', 'dismissed_by'])]
class WebsiteAlert extends Model
{
    /** @use HasFactory<WebsiteAlertFactory> */
    use HasFactory, HasUuids;

    protected $casts = [
        'due_at' => 'date',
        'resolved_at' => 'datetime',
        'dismissed_at' => 'datetime',
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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(WebsiteAlertRead::class, 'alert_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(WebsiteAlertActivity::class, 'alert_id');
    }
}
