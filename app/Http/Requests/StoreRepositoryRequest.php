<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepositoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('websites.manage');
    }

    public function rules(): array
    {
        return [
            'provider' => ['nullable', 'in:github,gitlab,bitbucket,outros'],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'default_branch' => ['nullable', 'string', 'max:100'],
            'environment_id' => ['nullable', 'uuid', 'exists:website_environments,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['name.required' => 'Informe o nome do repositório.', 'url.unique' => 'Este repositório já está cadastrado.'];
    }
}
