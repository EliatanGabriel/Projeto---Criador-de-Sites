<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE website_publications DROP CONSTRAINT IF EXISTS website_publications_result_check");
            DB::statement("ALTER TABLE website_publications ADD CONSTRAINT website_publications_result_check CHECK (result::text = ANY (ARRAY['pendente'::character varying, 'sucesso'::character varying, 'falha'::character varying, 'rollback'::character varying]::text[]))");
        }

        Schema::table('website_publications', function (Blueprint $table) {
            $table->string('source')->default('manual')->index();
            $table->unsignedBigInteger('github_deployment_id')->nullable()->unique();
        });

        Schema::create('website_repository_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('repository_id')->index();
            $table->foreign('repository_id')->references('id')->on('website_repositories')->cascadeOnDelete();
            $table->uuid('website_id')->index();
            $table->foreign('website_id')->references('id')->on('websites')->cascadeOnDelete();
            $table->string('delivery_id')->unique();
            $table->string('event_type')->index();
            $table->jsonb('payload_summary')->nullable();
            $table->unsignedBigInteger('github_deployment_id')->nullable()->index();
            $table->string('commit_sha')->nullable();
            $table->string('ref')->nullable();
            $table->string('outcome')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_repository_events');

        Schema::table('website_publications', function (Blueprint $table) {
            $table->dropColumn(['source', 'github_deployment_id']);
        });
    }
};
