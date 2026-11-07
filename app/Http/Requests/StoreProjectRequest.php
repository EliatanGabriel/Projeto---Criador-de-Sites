<?php

namespace App\Http\Requests;

use App\Models\ProjectTemplate;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('projects.create');
    }

    public function rules(): array
    {
        $templateId = $this->input('template_id');

        return [
            'demand_id' => ['required', 'uuid', 'exists:demands,id'],
            'template_id' => [
                'nullable', 'uuid', 'exists:project_templates,id',
                function ($attr, $value, $fail) use ($templateId) {
                    if ($value) {
                        $template = ProjectTemplate::find($value);
                        if ($template && ! $template->is_active) {
                            $fail('Template selecionado não está ativo.');
                        }
                    }
                },
            ],
            'assignments' => ['nullable', 'array'],
            'assignments.*' => [
                'nullable',
                function ($attr, $value, $fail) {
                    if (in_array($value, [null, '', 'unassigned', 'owner', 'developer'], true)) {
                        return;
                    }
                    $user = User::find($value);
                    if (! $user) {
                        $fail('Usuário de atribuição não encontrado.');
                        return;
                    }
                    if (! $user->is_active) {
                        $fail('Usuário de atribuição está inativo.');
                        return;
                    }
                    if (! ($user->hasRole('OWNER') || $user->hasRole('DEVELOPER'))) {
                        $fail('Somente usuários OWNER ou DEVELOPER podem ser responsáveis por tarefas.');
                    }
                },
            ],
            'excluded_task_ids' => [
                'nullable', 'array',
                function ($attr, $value, $fail) use ($templateId) {
                    if (! $value || ! $templateId) return;
                    $valid = \App\Models\ProjectTemplateTask::whereIn('id', $value)
                        ->whereHas('phase', fn ($q) => $q->where('template_id', $templateId))
                        ->pluck('id')->toArray();
                    foreach ($value as $id) {
                        if (! in_array($id, $valid, true)) {
                            $fail('Tarefa excluída não pertence ao template selecionado.');
                            return;
                        }
                    }
                },
            ],
            'excluded_task_ids.*' => ['uuid'],
        ];
    }

    public function messages(): array
    {
        return ['demand_id.required' => 'Selecione a demanda.'];
    }
}
