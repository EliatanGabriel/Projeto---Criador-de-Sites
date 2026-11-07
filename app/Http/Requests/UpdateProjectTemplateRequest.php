<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('templates.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'in:Site institucional,Landing page,Loja virtual,Portfólio,Site personalizado,Outros'],
            'version' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'phases' => ['nullable', 'array'],
            'phases.*.name' => ['required_with:phases', 'string', 'max:255'],
            'phases.*.tasks.*.title' => ['required_with:phases.*.tasks', 'string', 'max:255'],
            'phases.*.tasks.*.priority' => ['required_with:phases.*.tasks', 'in:low,medium,high,urgent'],
            'phases.*.tasks.*.suggested_role' => ['nullable', 'in:owner,developer'],
        ];
    }

    public function messages(): array
    {
        return ['name.required' => 'Informe o nome do template.'];
    }
}
