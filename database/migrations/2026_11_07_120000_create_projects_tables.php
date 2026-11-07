<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->uuid('notifiable_id');
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('demand_id')->unique()->index();
            $table->foreign('demand_id')->references('id')->on('demands')->restrictOnDelete();
            $table->uuid('client_id')->index();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->string('title')->index();
            $table->text('description')->nullable();
            $table->text('scope')->nullable();
            $table->uuid('technical_owner_id')->nullable()->index();
            $table->foreign('technical_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->date('started_at')->nullable();
            $table->date('planned_deadline')->nullable()->index();
            $table->date('completed_at')->nullable();
            $table->enum('status', ['planning', 'in_progress', 'in_review', 'blocked', 'completed', 'cancelled'])->default('planning')->index();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->index();
            $table->decimal('contracted_value', 12, 2)->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('blocked_reason')->nullable();
            $table->uuid('converted_by')->nullable()->index();
            $table->foreign('converted_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->index();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->uuid('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('project_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->index();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->uuid('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('event');
            $table->text('description')->nullable();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'created_at']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->index();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->string('title')->index();
            $table->text('description')->nullable();
            $table->uuid('assignee_id')->nullable()->index();
            $table->foreign('assignee_id')->references('id')->on('users')->nullOnDelete();
            $table->uuid('created_by')->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->index();
            $table->enum('status', ['todo', 'in_progress', 'in_review', 'blocked', 'done', 'cancelled'])->default('todo')->index();
            $table->date('started_at')->nullable();
            $table->date('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->uuid('completed_by')->nullable()->index();
            $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
            $table->text('blocked_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['project_id', 'status']);
            $table->index(['assignee_id', 'status']);
        });

        Schema::create('task_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id')->index();
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->uuid('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('event');
            $table->text('description')->nullable();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->index(['task_id', 'created_at']);
        });

        Schema::create('task_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id')->index();
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->uuid('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_histories');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('project_histories');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('notifications');
    }
};
