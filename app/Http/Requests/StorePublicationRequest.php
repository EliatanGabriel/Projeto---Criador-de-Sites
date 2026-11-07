<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('websites.publications') || $this->user()->can('websites.manage');
    }

    public function rules(): array
    {
        return [
            'environment_id' => ['required', 'uuid', 'exists:website_environments,id'],
            'version' => ['nullable', 'string', 'max:100'],
            'published_at' => ['required', 'date'],
            'responsible_id' => ['nullable', 'uuid', 'exists:users,id'],
            'description' => ['nullable', 'string'],
            'commit_reference' => ['nullable', 'string', 'max:100'],
            'result' => ['required', 'in:sucesso,falha,rollback'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['environment_id.required' => 'Selecione o ambiente.', 'result.in' => 'Resultado inválido.'];
    }
}
