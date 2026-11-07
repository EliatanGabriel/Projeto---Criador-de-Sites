<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWebsiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('websites.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'type' => ['nullable', 'in:institucional,landing_page,loja_virtual,portfolio,custom,outros'],
            'status' => ['required', 'in:desenvolvimento,publicado,suspenso,manutencao,desativado'],
            'started_at' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
            'technical_owner_id' => ['nullable', 'uuid', 'exists:users,id'],
            'technologies' => ['nullable', 'array'],
            'technologies.*' => ['string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'project_ids' => ['nullable', 'array'],
            'project_ids.*' => ['uuid', 'exists:projects,id'],
            'origin_project_id' => ['nullable', 'uuid', 'exists:projects,id'],
            'origin_relation_type' => ['nullable', 'in:origin,rework,maintenance,evolution'],
        ];
    }

    public function messages(): array
    {
        return ['name.required' => 'Informe o nome do site.'];
    }
}
