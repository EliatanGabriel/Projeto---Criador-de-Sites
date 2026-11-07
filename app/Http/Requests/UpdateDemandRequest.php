<?php

namespace App\Http\Requests;

use App\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('demands.edit');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'opportunity_id' => [
                'nullable', 'uuid', 'exists:opportunities,id',
                function ($attr, $value, $fail) {
                    if ($value && $this->client_id) {
                        $opp = Opportunity::find($value);
                        if ($opp && (string) $opp->client_id !== (string) $this->client_id) {
                            $fail('A oportunidade selecionada não pertence ao cliente escolhido.');
                        }
                    }
                },
            ],
            'service_type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'contracted_scope' => ['nullable', 'string'],
            'negotiated_value' => ['nullable', 'numeric', 'min:0'],
            'contracted_at' => ['nullable', 'date'],
            'desired_deadline' => ['nullable', 'date'],
            'priority' => ['required', 'in:baixa,normal,alta,urgente'],
            'internal_notes' => ['nullable', 'string'],
            'briefing' => ['nullable', 'array'],
            'briefing.*' => ['nullable', 'string'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,txt,zip,mp4'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Informe o título.',
            'files.*.max' => 'Cada arquivo deve ter no máximo 10 MB.',
            'files.*.mimes' => 'Tipo de arquivo não permitido.',
        ];
    }
}
