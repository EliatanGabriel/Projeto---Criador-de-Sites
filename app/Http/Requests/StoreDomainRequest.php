<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDomainRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('websites.manage');
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i'],
            'is_primary' => ['sometimes', 'boolean'],
            'registrar' => ['nullable', 'string', 'max:100'],
            'registered_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['required', 'in:ativo,suspenso,transferido,expirado'],
            'dns_notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['domain.required' => 'Informe o domínio.', 'domain.regex' => 'Domínio inválido.'];
    }
}
