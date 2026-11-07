<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title')->index();
            $table->uuid('client_id')->index();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->uuid('opportunity_id')->nullable()->index();
            $table->foreign('opportunity_id')->references('id')->on('opportunities')->nullOnDelete();
            $table->uuid('commercial_owner_id')->nullable()->index();
            $table->foreign('commercial_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->string('service_type')->nullable();
            $table->text('description')->nullable();
            $table->text('contracted_scope')->nullable();
            $table->decimal('negotiated_value', 12, 2)->nullable();
            $table->date('contracted_at')->nullable();
            $table->date('desired_deadline')->nullable()->index();
            $table->enum('priority', ['baixa', 'normal', 'alta', 'urgente'])->default('normal')->index();
            $table->enum('status', ['draft', 'submitted', 'under_review', 'needs_information', 'approved', 'rejected', 'cancelled'])->default('draft')->index();
            $table->text('internal_notes')->nullable();
            $table->uuid('technical_owner_id')->nullable()->index();
            $table->foreign('technical_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('demand_briefings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('demand_id')->unique();
            $table->foreign('demand_id')->references('id')->on('demands')->cascadeOnDelete();
            $table->text('objectives')->nullable();
            $table->text('target_audience')->nullable();
            $table->text('business_description')->nullable();
            $table->text('products_services')->nullable();
            $table->text('desired_pages')->nullable();
            $table->text('desired_features')->nullable();
            $table->text('references')->nullable();
            $table->text('visual_identity')->nullable();
            $table->text('colors_preferences')->nullable();
            $table->text('available_content')->nullable();
            $table->string('domain_info')->nullable();
            $table->string('hosting_info')->nullable();
            $table->text('additional_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('demand_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('demand_id')->index();
            $table->foreign('demand_id')->references('id')->on('demands')->cascadeOnDelete();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->uuid('uploaded_by')->nullable()->index();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('demand_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('demand_id')->index();
            $table->foreign('demand_id')->references('id')->on('demands')->cascadeOnDelete();
            $table->uuid('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('event');
            $table->text('description')->nullable();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->index(['demand_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demand_histories');
        Schema::dropIfExists('demand_attachments');
        Schema::dropIfExists('demand_briefings');
        Schema::dropIfExists('demands');
    }
};
