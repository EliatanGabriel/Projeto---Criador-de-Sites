<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_repositories', function (Blueprint $table) {
            $table->string('github_full_name')->nullable()->index();
            $table->string('last_commit_sha')->nullable();
            $table->timestamp('last_commit_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_sync_attempt_at')->nullable();
            $table->string('last_sync_status')->nullable()->index();
            $table->string('last_sync_error')->nullable();
            $table->unsignedInteger('open_pull_requests_count')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('website_repositories', function (Blueprint $table) {
            $table->dropColumn([
                'github_full_name',
                'last_commit_sha',
                'last_commit_at',
                'last_synced_at',
                'last_sync_attempt_at',
                'last_sync_status',
                'last_sync_error',
                'open_pull_requests_count',
            ]);
        });
    }
};
