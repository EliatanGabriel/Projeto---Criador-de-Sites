<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['demand_id', 'objectives', 'target_audience', 'business_description', 'products_services', 'desired_pages', 'desired_features', 'references', 'visual_identity', 'colors_preferences', 'available_content', 'domain_info', 'hosting_info', 'additional_notes'])]
class DemandBriefing extends Model
{
    use HasUuids;

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function uniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }

    public function demand(): BelongsTo
    {
        return $this->belongsTo(Demand::class);
    }
}
