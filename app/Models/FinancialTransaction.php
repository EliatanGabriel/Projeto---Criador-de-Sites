<?php

namespace App\Models;

use Database\Factories\FinancialTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'category_id', 'client_id', 'project_id', 'description', 'amount', 'status', 'due_at', 'paid_at', 'currency', 'notes', 'created_by'])]
class FinancialTransaction extends Model
{
    /** @use HasFactory<FinancialTransactionFactory> */
    use HasFactory, HasUuids;

    protected $casts = [
        'amount' => 'decimal:2',
        'due_at' => 'date',
        'paid_at' => 'date',
    ];

    public function uniqueIds(): array
    {
        return ['id'];
    }

    public function uniqueId(): string
    {
        return (string) \Illuminate\Support\Str::uuid7();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PaymentRecord::class, 'transaction_id');
    }

    public function expenseRecords(): HasMany
    {
        return $this->hasMany(ExpenseRecord::class, 'transaction_id');
    }

    public function paidTotal(): float
    {
        return (float) $this->payments()->sum('amount');
    }
}
