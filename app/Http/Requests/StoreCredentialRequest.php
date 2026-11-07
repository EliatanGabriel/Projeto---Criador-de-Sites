<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCredentialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('credentials.view') && $this->user()->hasRole('OWNER');
    }

    public function rules(): array
    {
        return [
            'website_id' => ['nullable', 'uuid', 'exists:websites,id'],
            'hosting_id' => ['nullable', 'uuid', 'exists:hostings,id'],
            'domain_id' => ['nullable', 'uuid', 'exists:website_domains,id'],
            'environment_id' => ['nullable', 'uuid', 'exists:website_environments,id'],
            'type' => ['required', 'in:hosting_admin,registrar,ftp,cms,service_token,outro'],
            'label' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'secret' => ['required', 'string', 'min:4', 'max:5000'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['label.required' => 'Informe o rótulo.', 'secret.required' => 'Informe o segredo.', 'secret.min' => 'Segredo muito curto.'];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $count = collect([$this->website_id, $this->hosting_id, $this->domain_id, $this->environment_id])->filter()->count();
            if ($count === 0) {
                $validator->errors()->add('website_id', 'Selecione um ativo (site, hospedagem, domínio ou ambiente).');
            } elseif ($count > 1) {
                $validator->errors()->add('website_id', 'Selecione apenas um ativo por credencial.');
            }
        });
    }
}
