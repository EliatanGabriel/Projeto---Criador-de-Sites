<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid7(),
            'project_id' => Project::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'assignee_id' => User::factory(),
            'created_by' => User::factory(),
            'priority' => 'medium',
            'status' => 'todo',
            'started_at' => now()->toDateString(),
            'due_at' => now()->addDays(14)->toDateString(),
            'completed_at' => null,
            'completed_by' => null,
            'blocked_reason' => null,
            'notes' => null,
        ];
    }

    public function done(): static
    {
        return $this->state(fn () => ['status' => 'done', 'completed_at' => now()]);
    }

    public function blocked(): static
    {
        return $this->state(fn () => ['status' => 'blocked', 'blocked_reason' => 'Aguardando material']);
    }
}
