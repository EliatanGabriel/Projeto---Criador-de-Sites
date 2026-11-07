<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_health_runs', function (Blueprint $table) {
            $table->bigIncrements('scheduled_run_id');
            $table->uuid('domain_id')->index();
            $table->foreign('domain_id')->references('id')->on('website_domains')->cascadeOnDelete();
            $table->uuid('website_id')->index();
            $table->foreign('website_id')->references('id')->on('websites')->cascadeOnDelete();
            $table->date('scheduled_for')->index();
            $table->string('status')->default('pending')->index();
            $table->timestampTz('dispatched_at')->nullable();
            $table->timestampTz('applied_at')->nullable();
            $table->timestamps();
            $table->unique(['domain_id', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_health_runs');
    }
};
