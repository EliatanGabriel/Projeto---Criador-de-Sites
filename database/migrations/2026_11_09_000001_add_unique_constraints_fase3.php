<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // Clients: document/email únicos (excluindo nulos e soft-deleted)
        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS clients_document_unique_partial ON clients (document) WHERE document IS NOT NULL AND deleted_at IS NULL');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS clients_email_unique_partial ON clients (LOWER(email)) WHERE email IS NOT NULL AND deleted_at IS NULL');
        } else {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS clients_document_unique_partial ON clients (document) WHERE document IS NOT NULL AND deleted_at IS NULL');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS clients_email_unique_partial ON clients (email) WHERE email IS NOT NULL AND deleted_at IS NULL');
        }

        // Financeiro: 1 lançamento de receita por projeto (parcelas via payment_records)
        if ($driver === 'pgsql') {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS financial_project_receita_unique_partial ON financial_transactions (project_id) WHERE project_id IS NOT NULL AND type = 'receita' AND status IN ('previsto', 'pendente')");
        } else {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS financial_project_receita_unique_partial ON financial_transactions (project_id) WHERE project_id IS NOT NULL AND type = 'receita' AND status IN ('previsto', 'pendente')");
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS clients_document_unique_partial');
        DB::statement('DROP INDEX IF EXISTS clients_email_unique_partial');
        DB::statement('DROP INDEX IF EXISTS financial_project_receita_unique_partial');
    }
};
