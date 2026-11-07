<?php

namespace Database\Seeders;

use App\Models\ProjectTemplate;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectTemplateSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $owner = User::whereHas('roles', fn ($q) => $q->where('name', 'OWNER'))->first();

        $template = ProjectTemplate::firstOrCreate(
            ['name' => 'Site institucional padrão'],
            [
                'description' => 'Modelo padrão para sites institucionais.',
                'category' => 'Site institucional',
                'version' => '1.0',
                'is_active' => true,
                'created_by' => $owner?->id,
                'updated_by' => $owner?->id,
            ]
        );

        if ($template->phases()->count() === 0) {
            $phases = [
                ['name' => 'Planejamento', 'description' => 'Análise e arquitetura', 'tasks' => [
                    ['title' => 'Analisar briefing', 'priority' => 'high', 'suggested_role' => 'owner', 'estimated_days' => 2],
                    ['title' => 'Definir arquitetura', 'priority' => 'high', 'suggested_role' => 'owner', 'estimated_days' => 3],
                    ['title' => 'Organizar materiais', 'priority' => 'medium', 'suggested_role' => 'owner', 'estimated_days' => 2],
                ]],
                ['name' => 'Desenvolvimento', 'description' => 'Implementação', 'tasks' => [
                    ['title' => 'Criar estrutura inicial', 'priority' => 'high', 'suggested_role' => 'developer', 'estimated_days' => 5],
                    ['title' => 'Desenvolver páginas', 'priority' => 'high', 'suggested_role' => 'developer', 'estimated_days' => 15],
                    ['title' => 'Implementar funcionalidades', 'priority' => 'medium', 'suggested_role' => 'developer', 'estimated_days' => 10],
                ]],
                ['name' => 'Revisão', 'description' => 'Qualidade', 'tasks' => [
                    ['title' => 'Revisar responsividade', 'priority' => 'medium', 'suggested_role' => 'developer', 'estimated_days' => 3],
                    ['title' => 'Testar funcionalidades', 'priority' => 'high', 'suggested_role' => 'developer', 'estimated_days' => 4],
                    ['title' => 'Corrigir problemas encontrados', 'priority' => 'medium', 'suggested_role' => 'developer', 'estimated_days' => 5],
                ]],
                ['name' => 'Publicação', 'description' => 'Deploy', 'tasks' => [
                    ['title' => 'Configurar hospedagem', 'priority' => 'medium', 'suggested_role' => 'owner', 'estimated_days' => 2],
                    ['title' => 'Configurar domínio', 'priority' => 'medium', 'suggested_role' => 'owner', 'estimated_days' => 1],
                    ['title' => 'Publicar site', 'priority' => 'high', 'suggested_role' => 'owner', 'estimated_days' => 1],
                    ['title' => 'Validar publicação', 'priority' => 'high', 'suggested_role' => 'owner', 'estimated_days' => 1],
                ]],
            ];

            foreach ($phases as $pi => $phase) {
                $phaseModel = $template->phases()->create([
                    'name' => $phase['name'],
                    'description' => $phase['description'],
                    'order' => $pi + 1,
                ]);
                foreach ($phase['tasks'] as $ti => $task) {
                    $phaseModel->tasks()->create([
                        'title' => $task['title'],
                        'priority' => $task['priority'],
                        'suggested_role' => $task['suggested_role'],
                        'estimated_days' => $task['estimated_days'],
                        'order' => $ti + 1,
                        'is_required' => true,
                    ]);
                }
            }
        }
    }
}
