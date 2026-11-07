<?php

namespace App\Http\Requests;

use App\Enums\OpportunityStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('commercial.edit');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'client_id' => ['nullable', 'uuid', 'exists:clients,id'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'responsible_id' => ['nullable', 'uuid', 'exists:users,id'],
            'service_type' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
            'negotiated_value' => ['nullable', 'numeric', 'min:0'],
            'opened_at' => ['nullable', 'date'],
            'expected_close_at' => ['nullable', 'date', 'after_or_equal:opened_at'],
            'status' => ['required', 'in:'.implode(',', OpportunityStatus::values())],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Informe o título.',
            'status.in' => 'Status inválido.',
        ];
    }
}
