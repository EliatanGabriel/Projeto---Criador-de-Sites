<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_domain_health_states', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('domain_id')->unique()->index();
            $table->foreign('domain_id')->references('id')->on('website_domains')->cascadeOnDelete();
            $table->uuid('website_id')->index();
            $table->foreign('website_id')->references('id')->on('websites')->cascadeOnDelete();
            $table->uuid('environment_id')->nullable()->index();
            $table->foreign('environment_id')->references('id')->on('website_environments')->cascadeOnDelete();
            $table->string('state')->default('operational')->index();
            $table->uuid('cycle_id')->nullable();
            $table->unsignedInteger('cycle_number')->default(0);
            $table->timestampTz('cycle_started_at')->nullable();
            $table->unsignedSmallInteger('consecutive_failures')->default(0);
            $table->unsignedBigInteger('last_applied_run_id')->nullable()->index();
            $table->timestampTz('last_checked_at')->nullable();
            $table->timestampTz('last_ok_at')->nullable();
            $table->timestampTz('last_recovered_at')->nullable();
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX website_domain_health_states_cycle_unique ON website_domain_health_states (cycle_id) WHERE cycle_id IS NOT NULL');

        Schema::create('website_health_checks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('domain_id')->index();
            $table->foreign('domain_id')->references('id')->on('website_domains')->cascadeOnDelete();
            $table->uuid('environment_id')->nullable()->index();
            $table->foreign('environment_id')->references('id')->on('website_environments')->cascadeOnDelete();
            $table->uuid('website_id')->index();
            $table->foreign('website_id')->references('id')->on('websites')->cascadeOnDelete();
            $table->string('trigger_source')->index();
            $table->unsignedBigInteger('scheduled_run_id')->nullable()->index();
            $table->uuid('cycle_id')->nullable()->index();
            $table->string('state_at_check')->nullable();
            $table->text('target_host')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable()->index();
            $table->boolean('ok')->index();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->boolean('ssl_valid')->nullable();
            $table->timestampTz('ssl_expires_at')->nullable()->index();
            $table->string('error_code')->nullable()->index();
            $table->timestampTz('checked_at')->index();
            $table->timestamps();
            $table->index(['domain_id', 'checked_at']);
            $table->index(['website_id', 'checked_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("CREATE UNIQUE INDEX website_health_checks_run_unique ON website_health_checks (scheduled_run_id) WHERE trigger_source = 'scheduled'");
            DB::statement("CREATE SEQUENCE IF NOT EXISTS website_health_check_schedule_seq");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('website_health_checks');
        Schema::dropIfExists('website_domain_health_states');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP SEQUENCE IF EXISTS website_health_check_schedule_seq');
        }
    }
};
