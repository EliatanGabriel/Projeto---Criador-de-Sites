<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('category')->index();
            $table->string('version')->default('1.0');
            $table->boolean('is_active')->default(true)->index();
            $table->uuid('created_by')->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->uuid('updated_by')->nullable()->index();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_template_phases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('template_id')->index();
            $table->foreign('template_id')->references('id')->on('project_templates')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('order')->default(0)->index();
            $table->timestamps();
        });

        Schema::create('project_template_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('phase_id')->index();
            $table->foreign('phase_id')->references('id')->on('project_template_phases')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('suggested_role', ['owner', 'developer'])->nullable();
            $table->integer('estimated_days')->nullable()->unsigned();
            $table->integer('order')->default(0)->index();
            $table->boolean('is_required')->default(true);
            $table->timestamps();
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->uuid('template_id')->nullable()->index();
            $table->foreign('template_id')->references('id')->on('project_templates')->nullOnDelete();
            $table->string('template_version')->nullable();
            $table->jsonb('template_snapshot')->nullable();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->uuid('template_task_id')->nullable()->index();
            $table->string('phase_name')->nullable()->index();
            $table->integer('phase_order')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['template_task_id', 'phase_name', 'phase_order']);
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('template_id');
            $table->dropColumn(['template_version', 'template_snapshot']);
        });
        Schema::dropIfExists('project_template_tasks');
        Schema::dropIfExists('project_template_phases');
        Schema::dropIfExists('project_templates');
    }
};
