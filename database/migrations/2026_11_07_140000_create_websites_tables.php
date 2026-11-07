<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id')->index();
            $table->foreign('client_id')->references('id')->on('clients')->restrictOnDelete();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table
                ->enum('status', ['desenvolvimento', 'publicado', 'suspenso', 'manutencao', 'desativado'])
                ->default('desenvolvimento')
                ->index();
            $table->date('started_at')->nullable();
            $table->date('published_at')->nullable();
            $table->uuid('technical_owner_id')->nullable()->index();
            $table->foreign('technical_owner_id')->references('id')->on('users')->nullOnDelete();
            $table->jsonb('technologies')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('website_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_id')->index();
            $table->foreign('website_id')->references('id')->on('websites')->cascadeOnDelete();
            $table->uuid('project_id')->index();
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->enum('relation_type', ['origin', 'rework', 'maintenance', 'evolution'])->index();
            $table->date('registered_at')->nullable();
            $table->timestamps();
            $table->unique(['website_id', 'project_id']);
        });

        Schema::create('hostings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider')->index();
            $table->string('name')->index();
            $table->string('plan')->nullable();
            $table->string('account_identifier')->nullable();
            $table->enum('status', ['ativo', 'suspenso', 'cancelado'])->default('ativo')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('website_domains', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_id')->index();
            $table->foreign('website_id')->references('id')->on('websites')->cascadeOnDelete();
            $table->string('domain')->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->string('registrar')->nullable();
            $table->date('registered_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->enum('status', ['ativo', 'suspenso', 'transferido', 'expirado'])->default('ativo')->index();
            $table->text('dns_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('website_environments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_id')->index();
            $table->foreign('website_id')->references('id')->on('websites')->cascadeOnDelete();
            $table->uuid('hosting_id')->nullable()->index();
            $table->foreign('hosting_id')->references('id')->on('hostings')->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['development', 'staging', 'production'])->index();
            $table->string('url')->nullable();
            $table->enum('status', ['ativo', 'suspenso', 'offline', 'desativado'])->default('ativo')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('website_repositories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_id')->index();
            $table->foreign('website_id')->references('id')->on('websites')->cascadeOnDelete();
            $table->uuid('environment_id')->nullable()->index();
            $table->foreign('environment_id')->references('id')->on('website_environments')->nullOnDelete();
            $table->string('provider')->nullable();
            $table->string('name');
            $table->string('url')->index();
            $table->string('default_branch')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('website_publications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_id')->index();
            $table->foreign('website_id')->references('id')->on('websites')->cascadeOnDelete();
            $table->uuid('environment_id')->index();
            $table->foreign('environment_id')->references('id')->on('website_environments')->restrictOnDelete();
            $table->string('version')->nullable()->index();
            $table->timestamp('published_at')->index();
            $table->uuid('responsible_id')->nullable()->index();
            $table->foreign('responsible_id')->references('id')->on('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('commit_reference')->nullable();
            $table->enum('result', ['sucesso', 'falha', 'rollback'])->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_id')->index();
            $table->foreign('website_id')->references('id')->on('websites')->cascadeOnDelete();
            $table->uuid('project_id')->nullable()->index();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->uuid('task_id')->nullable()->index();
            $table->foreign('task_id')->references('id')->on('tasks')->nullOnDelete();
            $table->enum('type', ['corrigir', 'evoluir', 'seguranca', 'performance', 'conteudo', 'outro'])->index();
            $table->text('description');
            $table->uuid('responsible_id')->nullable()->index();
            $table->foreign('responsible_id')->references('id')->on('users')->nullOnDelete();
            $table->enum('status', ['aberta', 'em_andamento', 'concluida', 'cancelada'])->default('aberta')->index();
            $table->date('opened_at')->index();
            $table->date('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable()->index();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_id')->nullable()->index();
            $table->foreign('website_id')->references('id')->on('websites')->cascadeOnDelete();
            $table->uuid('hosting_id')->nullable()->index();
            $table->foreign('hosting_id')->references('id')->on('hostings')->cascadeOnDelete();
            $table->uuid('domain_id')->nullable()->index();
            $table->foreign('domain_id')->references('id')->on('website_domains')->cascadeOnDelete();
            $table->uuid('environment_id')->nullable()->index();
            $table->foreign('environment_id')->references('id')->on('website_environments')->cascadeOnDelete();
            $table->enum('type', ['hosting_admin', 'registrar', 'ftp', 'cms', 'service_token', 'outro'])->index();
            $table->string('label');
            $table->string('username')->nullable();
            $table->text('secret_encrypted');
            $table->text('notes')->nullable();
            $table->timestamp('last_rotated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('credential_access_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('credential_id')->index();
            $table->foreign('credential_id')->references('id')->on('credentials')->cascadeOnDelete();
            $table->uuid('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->enum('action', ['revealed', 'updated', 'created'])->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        // Integridade: exatamente 1 parent em credentials (direto no PostgreSQL, sem depender de HTTP)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE credentials ADD CONSTRAINT credentials_exactly_one_parent CHECK ((CASE WHEN website_id IS NOT NULL THEN 1 ELSE 0 END + CASE WHEN hosting_id IS NOT NULL THEN 1 ELSE 0 END + CASE WHEN domain_id IS NOT NULL THEN 1 ELSE 0 END + CASE WHEN environment_id IS NOT NULL THEN 1 ELSE 0 END) = 1)");
        } else {
            // SQLite não suporta ALTER TABLE ADD CONSTRAINT – proteção via validação aplicacional + triggers manuais nos testes.
            DB::statement('CREATE TRIGGER credentials_exactly_one_parent_check BEFORE INSERT ON credentials FOR EACH ROW WHEN ((CASE WHEN NEW.website_id IS NOT NULL THEN 1 ELSE 0 END + CASE WHEN NEW.hosting_id IS NOT NULL THEN 1 ELSE 0 END + CASE WHEN NEW.domain_id IS NOT NULL THEN 1 ELSE 0 END + CASE WHEN NEW.environment_id IS NOT NULL THEN 1 ELSE 0 END) != 1) BEGIN SELECT RAISE(ABORT, \'credentials exige exatamente um parent\'); END');
            DB::statement('CREATE TRIGGER credentials_exactly_one_parent_update BEFORE UPDATE ON credentials FOR EACH ROW WHEN ((CASE WHEN NEW.website_id IS NOT NULL THEN 1 ELSE 0 END + CASE WHEN NEW.hosting_id IS NOT NULL THEN 1 ELSE 0 END + CASE WHEN NEW.domain_id IS NOT NULL THEN 1 ELSE 0 END + CASE WHEN NEW.environment_id IS NOT NULL THEN 1 ELSE 0 END) != 1) BEGIN SELECT RAISE(ABORT, \'credentials exige exatamente um parent\'); END');
        }

        // Unicidades condicionais (PG/SQLite via raw – whereNull deleted_at)
        DB::statement('CREATE UNIQUE INDEX websites_domains_domain_unique ON website_domains (lower(domain)) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX websites_domains_primary_unique ON website_domains (website_id) WHERE is_primary = true AND deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX websites_repositories_url_unique ON website_repositories (url) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX website_projects_origin_unique ON website_projects (website_id) WHERE relation_type = \'origin\'');
        DB::statement('CREATE UNIQUE INDEX website_environments_prod_unique ON website_environments (website_id) WHERE type = \'production\' AND status = \'ativo\' AND deleted_at IS NULL');
    }

    public function down(): void
    {
        try {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE credentials DROP CONSTRAINT IF EXISTS credentials_exactly_one_parent');
            } else {
                DB::statement('DROP TRIGGER IF EXISTS credentials_exactly_one_parent_check');
                DB::statement('DROP TRIGGER IF EXISTS credentials_exactly_one_parent_update');
            }
        } catch (\Throwable $e) {
        }
        Schema::dropIfExists('credential_access_logs');
        Schema::dropIfExists('credentials');
        Schema::dropIfExists('maintenance_records');
        Schema::dropIfExists('website_publications');
        Schema::dropIfExists('website_repositories');
        Schema::dropIfExists('website_environments');
        Schema::dropIfExists('website_domains');
        Schema::dropIfExists('hostings');
        Schema::dropIfExists('website_projects');
        Schema::dropIfExists('websites');
    }
};
