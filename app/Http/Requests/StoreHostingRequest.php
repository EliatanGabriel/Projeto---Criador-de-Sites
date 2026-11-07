<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('hostings.manage');
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'plan' => ['nullable', 'string', 'max:100'],
            'account_identifier' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:ativo,suspenso,cancelado'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['provider.required' => 'Informe o provedor.', 'name.required' => 'Informe o nome do serviço.'];
    }
}
