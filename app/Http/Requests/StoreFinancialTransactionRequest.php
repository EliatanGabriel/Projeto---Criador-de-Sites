<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:receita,despesa'],
            'category_id' => ['required', 'uuid', 'exists:financial_categories,id'],
            'client_id' => ['nullable', 'uuid', 'exists:clients,id'],
            'project_id' => ['nullable', 'uuid', 'exists:projects,id'],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'status' => ['nullable', 'in:previsto,pendente,recebido,pago,cancelado'],
            'due_at' => ['nullable', 'date'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $category = \App\Models\FinancialCategory::find($this->category_id);
            if ($category && $category->type !== $this->type) {
                $validator->errors()->add('category_id', 'A categoria selecionada não corresponde ao tipo da transação.');
            }
            if ($this->project_id) {
                $project = \App\Models\Project::find($this->project_id);
                if ($project && $this->client_id && (string) $project->client_id !== (string) $this->client_id) {
                    $validator->errors()->add('project_id', 'O projeto não pertence ao cliente selecionado.');
                }
            }
            if (in_array($this->input('status'), ['previsto', 'pendente'], true) && $this->boolean('project_id')) {
                // previsto/pendente são apenas estado de cobrança, não recebido
            }
            if ($this->input('amount') > 9999999999.99) {
                $validator->errors()->add('amount', 'Valor máximo excedido.');
            }
        });
    }
}
