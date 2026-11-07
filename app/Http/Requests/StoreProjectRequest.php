<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('projects.create');
    }

    public function rules(): array
    {
        return [
            'demand_id' => ['required', 'uuid', 'exists:demands,id'],
            'template_id' => ['nullable', 'uuid', 'exists:project_templates,id'],
            'assignments' => ['nullable', 'array'],
            'assignments.*' => ['nullable', 'uuid', 'exists:users,id'],
            'excluded_task_ids' => ['nullable', 'array'],
            'excluded_task_ids.*' => ['uuid'],
        ];
    }

    public function messages(): array
    {
        return ['demand_id.required' => 'Selecione a demanda.'];
    }
}
