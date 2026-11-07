<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['alert_id', 'user_id', 'event', 'description', 'metadata', 'created_at'])]
class WebsiteAlertActivity extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $casts = ['metadata' => 'array', 'created_at' => 'datetime'];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function uniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(WebsiteAlert::class, 'alert_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
