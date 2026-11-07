<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->index();
            $table->string('type')->index();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type')->index();
            $table->uuid('category_id')->index();
            $table->foreign('category_id')->references('id')->on('financial_categories')->restrictOnDelete();
            $table->uuid('client_id')->nullable()->index();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->uuid('project_id')->nullable()->index();
            $table->foreign('project_id')->references('id')->on('projects')->restrictOnDelete();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('previsto')->index();
            $table->date('due_at')->nullable()->index();
            $table->date('paid_at')->nullable();
            $table->string('currency')->default('BRL');
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['type', 'status', 'due_at']);
        });

        Schema::create('payment_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaction_id')->index();
            $table->foreign('transaction_id')->references('id')->on('financial_transactions')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_at')->index();
            $table->string('method')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaction_id')->index();
            $table->foreign('transaction_id')->references('id')->on('financial_transactions')->restrictOnDelete();
            $table->string('vendor')->nullable();
            $table->string('invoice_ref')->nullable();
            $table->date('due_at')->nullable();
            $table->date('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_records');
        Schema::dropIfExists('payment_records');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('financial_categories');
    }
};
