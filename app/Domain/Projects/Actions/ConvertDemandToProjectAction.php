<?php

namespace App\Domain\Projects\Actions;

use App\Models\Demand;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ProjectCreated;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ConvertDemandToProjectAction
{
    public function __invoke(Demand $demand, User $user, ?string $templateId = null, array $assignments = [], array $excludedTaskIds = []): Project
    {
        if (! $user->can('demands.convert')) {
            abort(403, 'Apenas OWNERS podem converter demandas.');
        }
        if ($demand->status !== 'approved') {
            abort(422, 'Somente demandas aprovadas podem ser convertidas.');
        }

        $template = null;
        if ($templateId) {
            $template = ProjectTemplate::with('phases.tasks')->find($templateId);
            if (! $template || ! $template->is_active) {
                abort(422, 'Template selecionado não está ativo.');
            }
        }

        return DB::transaction(function () use ($demand, $user, $template, $assignments, $excludedTaskIds) {
            $locked = Demand::query()->whereKey($demand->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'approved') {
                abort(422, 'Demanda já foi alterada ou não está aprovada.');
            }

            if (Project::where('demand_id', $locked->id)->exists()) {
                abort(422, 'Esta demanda já foi convertida em projeto.');
            }

            $priorityMap = ['baixa' => 'low', 'normal' => 'medium', 'alta' => 'high', 'urgente' => 'urgent'];
            $snapshot = null;
            if ($template) {
                $template->load('phases.tasks');
                $snapshot = [
                    'template_id' => $template->id,
                    'template_name' => $template->name,
                    'version' => $template->version,
                    'phases' => $template->phases->map(fn ($p) => [
                        'name' => $p->name,
                        'description' => $p->description,
                        'order' => $p->order,
                        'tasks' => $p->tasks->map(fn ($t) => [
                            'id' => $t->id,
                            'title' => $t->title,
                            'description' => $t->description,
                            'priority' => $t->priority,
                            'suggested_role' => $t->suggested_role,
                            'estimated_days' => $t->estimated_days,
                            'order' => $t->order,
                            'is_required' => $t->is_required,
                        ])->toArray(),
                    ])->toArray(),
                ];
            }

            $project = Project::create([
                'demand_id' => $locked->id,
                'client_id' => $locked->client_id,
                'title' => $locked->title,
                'description' => $locked->description,
                'scope' => $locked->contracted_scope,
                'technical_owner_id' => $locked->technical_owner_id ?? $user->id,
                'started_at' => now()->toDateString(),
                'planned_deadline' => $locked->desired_deadline,
                'status' => 'planning',
                'priority' => $priorityMap[$locked->priority] ?? 'medium',
                'contracted_value' => $locked->negotiated_value,
                'internal_notes' => $locked->internal_notes,
                'converted_by' => $user->id,
                'converted_at' => now(),
                'template_id' => $template?->id,
                'template_version' => $template?->version,
                'template_snapshot' => $snapshot,
            ]);

            $project->members()->create(['user_id' => $user->id, 'role' => 'owner']);
            if ($locked->technical_owner_id && $locked->technical_owner_id !== $user->id) {
                $project->members()->firstOrCreate(['user_id' => $locked->technical_owner_id], ['role' => 'technical']);
            }

            if ($template) {
                $startedAt = now()->toDateString();
                foreach ($template->phases as $phase) {
                    foreach ($phase->tasks as $tempTask) {
                        if (in_array($tempTask->id, $excludedTaskIds, true)) {
                            continue;
                        }
                        if (! $tempTask->is_required && in_array($tempTask->id, $excludedTaskIds, true)) {
                            continue;
                        }
                        $assigneeId = $assignments[$tempTask->id] ?? null;
                        if (in_array($assigneeId, [null, '', 'unassigned', 'owner', 'developer'], true)) {
                            // Sugestão de papel não implica atribuição automática (disponibilidade)
                            $assigneeId = null;
                        } elseif ($assigneeId) {
                            $target = User::find($assigneeId);
                            if (! $target) {
                                abort(422, 'Usuário de atribuição não encontrado.');
                            }
                            if (! $target->is_active) {
                                abort(422, 'Usuário de atribuição está inativo.');
                            }
                            if (! ($target->hasRole('OWNER') || $target->hasRole('DEVELOPER'))) {
                                abort(422, 'Somente OWNER ou DEVELOPER podem ser responsáveis.');
                            }
                        }

                        $dueAt = null;
                        if ($tempTask->estimated_days !== null) {
                            $dueAt = now()->addDays((int) $tempTask->estimated_days)->toDateString();
                        }

                        $task = Task::create([
                            'project_id' => $project->id,
                            'title' => $tempTask->title,
                            'description' => $tempTask->description,
                            'assignee_id' => $assigneeId,
                            'created_by' => $user->id,
                            'priority' => $tempTask->priority,
                            'status' => 'todo',
                            'started_at' => $startedAt,
                            'due_at' => $dueAt,
                            'template_task_id' => $tempTask->id,
                            'phase_name' => $phase->name,
                            'phase_order' => $phase->order,
                        ]);

                        $task->histories()->create([
                            'user_id' => $user->id,
                            'event' => 'created_from_template',
                            'description' => sprintf('Tarefa gerada a partir do template %s (etapa %s).', $template->name, $phase->name),
                            'metadata' => ['template_id' => $template->id, 'template_task_id' => $tempTask->id, 'phase' => $phase->name],
                        ]);
                    }
                }
            }

            $project->histories()->create([
                'user_id' => $user->id,
                'event' => 'converted' . ($template ? 'with_template' : ''),
                'description' => $template
                    ? sprintf('Projeto criado a partir da demanda aprovada com template %s v%s.', $template->name, $template->version)
                    : 'Projeto criado a partir da demanda aprovada.',
                'metadata' => ['demand_id' => $locked->id, 'template_id' => $template?->id, 'template_version' => $template?->version],
            ]);

            $locked->histories()->create([
                'user_id' => $user->id,
                'event' => 'converted_to_project',
                'description' => 'Demanda convertida em projeto.',
                'from_status' => 'approved',
                'to_status' => 'approved',
                'metadata' => ['project_id' => $project->id, 'template_id' => $template?->id],
            ]);

            $notifiableUsers = [$user];
            if ($locked->technical_owner_id && $locked->technical_owner_id !== $user->id) {
                $tech = User::find($locked->technical_owner_id);
                if ($tech) $notifiableUsers[] = $tech;
            }
            if ($locked->commercial_owner_id) {
                $commercial = User::find($locked->commercial_owner_id);
                if ($commercial) $notifiableUsers[] = $commercial;
            }
            foreach (array_unique(array_map(fn ($u) => $u->id, $notifiableUsers)) as $uid) {
                $u = User::find($uid);
                $u?->notify(new ProjectCreated($project));
            }

            return $project;
        }, 3, function (QueryException $e) {
            if ($e->getCode() !== '23505') throw $e;
            abort(422, 'Esta demanda já foi convertida em projeto (concorrência).');
        });
    }
}
