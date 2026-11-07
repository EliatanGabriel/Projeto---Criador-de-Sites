<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('websites.maintenance') || $this->user()->can('websites.manage');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:corrigir,evoluir,seguranca,performance,conteudo,outro'],
            'description' => ['required', 'string'],
            'responsible_id' => ['nullable', 'uuid', 'exists:users,id'],
            'status' => ['required', 'in:aberta,em_andamento,concluida,cancelada'],
            'opened_at' => ['required', 'date'],
            'completed_at' => ['nullable', 'date'],
            'project_id' => ['nullable', 'uuid', 'exists:projects,id'],
            'task_id' => ['nullable', 'uuid', 'exists:tasks,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return ['type.in' => 'Tipo inválido.', 'description.required' => 'Informe a descrição.'];
    }
}
