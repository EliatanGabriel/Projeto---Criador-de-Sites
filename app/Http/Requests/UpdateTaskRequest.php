<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('tasks.edit');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assignee_id' => ['nullable', 'uuid', 'exists:users,id'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'status' => ['required', 'in:todo,in_progress,in_review,blocked,done,cancelled'],
            'started_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'blocked_reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Informe o título.',
            'blocked_reason.required_if' => 'Justificativa obrigatória para tarefas bloqueadas.',
        ];
    }
}
