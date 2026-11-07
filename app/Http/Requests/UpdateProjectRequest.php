<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('projects.edit');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scope' => ['nullable', 'string'],
            'technical_owner_id' => ['nullable', 'uuid', 'exists:users,id'],
            'started_at' => ['nullable', 'date'],
            'planned_deadline' => ['nullable', 'date'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'contracted_value' => ['nullable', 'numeric', 'min:0'],
            'internal_notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['title.required' => 'Informe o título.'];
    }
}
