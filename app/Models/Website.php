<?php

namespace App\Models;

use Database\Factories\WebsiteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['client_id', 'name', 'description', 'type', 'status', 'started_at', 'published_at', 'technical_owner_id', 'technologies', 'notes'])]
class Website extends Model
{
    /** @use HasFactory<WebsiteFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected $casts = [
        'started_at' => 'date',
        'published_at' => 'date',
        'technologies' => 'array',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function uniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function technicalOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technical_owner_id');
    }

    public function websiteProjects(): HasMany
    {
        return $this->hasMany(WebsiteProject::class);
    }

    public function projects(): HasManyThrough
    {
        return $this->hasManyThrough(Project::class, WebsiteProject::class, 'website_id', 'id', 'id', 'project_id');
    }

    public function originProject(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(Project::class, WebsiteProject::class, 'website_id', 'id', 'id', 'project_id')
            ->where('website_projects.relation_type', 'origin');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(WebsiteDomain::class);
    }

    public function environments(): HasMany
    {
        return $this->hasMany(WebsiteEnvironment::class);
    }

    public function repositories(): HasMany
    {
        return $this->hasMany(WebsiteRepository::class);
    }

    public function publications(): HasMany
    {
        return $this->hasMany(WebsitePublication::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class);
    }

    public function primaryDomain(): HasOne
    {
        return $this->hasOne(WebsiteDomain::class)->where('is_primary', true);
    }

    public function productionEnvironment(): HasOne
    {
        return $this->hasOne(WebsiteEnvironment::class)->where('type', 'production')->where('status', 'ativo');
    }
}
