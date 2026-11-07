<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnvironmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('websites.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:development,staging,production'],
            'url' => ['nullable', 'url', 'max:255'],
            'hosting_id' => ['nullable', 'uuid', 'exists:hostings,id'],
            'status' => ['required', 'in:ativo,suspenso,offline,desativado'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['name.required' => 'Informe o nome do ambiente.'];
    }
}
