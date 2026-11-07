<?php

namespace Database\Seeders;

use App\Models\FinancialCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(ProjectTemplateSeeder::class);

        foreach (['Serviço', 'Manutenção', 'Hospedagem', 'Domínio'] as $name) {
            FinancialCategory::firstOrCreate(['name' => $name, 'type' => 'receita'], ['is_system' => true]);
        }
        foreach (['Hospedagem', 'Ferramentas', 'Operacional'] as $name) {
            FinancialCategory::firstOrCreate(['name' => $name, 'type' => 'despesa'], ['is_system' => true]);
        }
    }
}
