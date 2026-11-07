<?php

namespace App\Models;

use Database\Factories\ExpenseRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['transaction_id', 'vendor', 'invoice_ref', 'due_at', 'paid_at'])]
class ExpenseRecord extends Model
{
    /** @use HasFactory<ExpenseRecordFactory> */
    use HasFactory, HasUuids;

    protected $casts = [
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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'transaction_id');
    }
}
