<?php

namespace Database\Factories;

use App\Models\MaintenanceRecord;
use App\Models\Website;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MaintenanceRecord>
 */
class MaintenanceRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'website_id' => Website::factory(),
            'project_id' => null,
            'task_id' => null,
            'type' => 'corrigir',
            'description' => fake()->sentence(),
            'responsible_id' => null,
            'status' => 'aberta',
            'opened_at' => now()->toDateString(),
            'completed_at' => null,
            'notes' => null,
            'created_by' => null,
        ];
    }
}
