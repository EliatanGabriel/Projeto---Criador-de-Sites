<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostings', function (Blueprint $table) {
            $table->date('next_renewal_at')->nullable()->index();
        });

        Schema::table('website_environments', function (Blueprint $table) {
            $table->date('ssl_expires_at')->nullable()->index();
        });

        Schema::create('website_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_id')->index();
            $table->foreign('website_id')->references('id')->on('websites')->cascadeOnDelete();
            $table->string('alert_type')->index();
            $table->string('severity')->index();
            $table->string('source_type')->nullable()->index();
            $table->uuid('source_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_at')->index();
            $table->string('fingerprint')->index();
            $table->uuid('assigned_to')->nullable()->index();
            $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            $table->string('status')->default('open')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->uuid('resolved_by')->nullable()->index();
            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('dismissed_at')->nullable();
            $table->uuid('dismissed_by')->nullable()->index();
            $table->foreign('dismissed_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('website_alert_reads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('alert_id')->index();
            $table->foreign('alert_id')->references('id')->on('website_alerts')->cascadeOnDelete();
            $table->uuid('user_id')->index();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['alert_id', 'user_id']);
        });

        Schema::create('website_alert_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('alert_id')->index();
            $table->foreign('alert_id')->references('id')->on('website_alerts')->cascadeOnDelete();
            $table->uuid('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('event')->index();
            $table->text('description')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        DB::statement('CREATE UNIQUE INDEX website_alerts_open_fingerprint_unique ON website_alerts (fingerprint) WHERE status = \'open\'');
    }

    public function down(): void
    {
        try {
            DB::statement('DROP INDEX IF EXISTS website_alerts_open_fingerprint_unique');
        } catch (\Throwable $e) {
        }
        Schema::dropIfExists('website_alert_activities');
        Schema::dropIfExists('website_alert_reads');
        Schema::dropIfExists('website_alerts');
        Schema::table('website_environments', function (Blueprint $table) {
            $table->dropColumn('ssl_expires_at');
        });
        Schema::table('hostings', function (Blueprint $table) {
            $table->dropColumn('next_renewal_at');
        });
    }
};
