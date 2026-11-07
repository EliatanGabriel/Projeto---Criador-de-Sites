<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['domain_id', 'website_id', 'scheduled_for', 'status', 'dispatched_at', 'applied_at'])]
class SiteHealthRun extends Model
{
    protected $primaryKey = 'scheduled_run_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $casts = [
        'scheduled_for' => 'date',
        'dispatched_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(WebsiteDomain::class, 'domain_id');
    }

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class, 'website_id');
    }
}
