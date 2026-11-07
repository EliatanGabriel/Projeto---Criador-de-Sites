<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->uuid('client_id')->nullable()->index();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->string('contact_name')->nullable();
            $table->uuid('responsible_id')->nullable()->index();
            $table->foreign('responsible_id')->references('id')->on('users')->nullOnDelete();
            $table->string('service_type')->nullable();
            $table->text('description')->nullable();
            $table->decimal('estimated_value', 12, 2)->nullable();
            $table->decimal('negotiated_value', 12, 2)->nullable();
            $table->date('opened_at')->nullable()->index();
            $table->date('expected_close_at')->nullable()->index();
            $table->enum('status', ['nova', 'em_contato', 'em_negociacao', 'aguardando_cliente', 'ganha', 'perdida', 'cancelada'])->default('nova')->index();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('opportunity_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('opportunity_id')->index();
            $table->foreign('opportunity_id')->references('id')->on('opportunities')->cascadeOnDelete();
            $table->uuid('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_activities');
        Schema::dropIfExists('opportunities');
    }
};
