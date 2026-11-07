<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('clients.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:pf,pj'],
            'document' => ['nullable', 'string', 'max:18'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'zip' => ['nullable', 'string', 'max:9'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:ativo,inativo'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome ou razão social.',
            'type.in' => 'Tipo inválido.',
            'email.email' => 'E-mail inválido.',
            'state.size' => 'UF deve ter 2 caracteres.',
            'status.in' => 'Status inválido.',
        ];
    }
}
