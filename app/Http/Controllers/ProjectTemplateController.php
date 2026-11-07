<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectTemplateRequest;
use App\Http\Requests\UpdateProjectTemplateRequest;
use App\Models\ProjectTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProjectTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = ProjectTemplate::query()->withCount('projects')->orderBy('name');

        if ($request->search) $query->where('name', 'like', "%{$request->search}%");
        if ($request->category) $query->where('category', $request->category);
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'ativo');
        }

        return Inertia::render('templates/index', [
            'templates' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'category', 'status']),
            'categories' => ['Site institucional', 'Landing page', 'Loja virtual', 'Portfólio', 'Site personalizado', 'Outros'],
        ]);
    }

    public function create()
    {
        return Inertia::render('templates/create', [
            'categories' => ['Site institucional', 'Landing page', 'Loja virtual', 'Portfólio', 'Site personalizado', 'Outros'],
        ]);
    }

    public function store(StoreProjectTemplateRequest $request)
    {
        $data = $request->validated();
        $phases = $data['phases'] ?? [];

        $template = DB::transaction(function () use ($request, $data, $phases) {
            $template = ProjectTemplate::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'],
                'version' => $data['version'] ?? '1.0',
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            foreach (array_values($phases) as $pi => $phase) {
                $phaseModel = $template->phases()->create([
                    'name' => $phase['name'],
                    'description' => $phase['description'] ?? null,
                    'order' => $phase['order'] ?? ($pi + 1),
                ]);
                foreach (array_values($phase['tasks'] ?? []) as $ti => $task) {
                    $phaseModel->tasks()->create([
                        'title' => $task['title'],
                        'description' => $task['description'] ?? null,
                        'priority' => $task['priority'] ?? 'medium',
                        'suggested_role' => $task['suggested_role'] ?? null,
                        'estimated_days' => $task['estimated_days'] ?? null,
                        'order' => $task['order'] ?? ($ti + 1),
                        'is_required' => $task['is_required'] ?? true,
                    ]);
                }
            }
            return $template;
        });

        return redirect()->route('templates.show', $template)->with('success', 'Template criado com sucesso.');
    }

    public function show(ProjectTemplate $template)
    {
        $template->load(['phases.tasks', 'creator:id,name']);
        return Inertia::render('templates/show', ['template' => $template]);
    }

    public function edit(ProjectTemplate $template)
    {
        $template->load('phases.tasks');
        return Inertia::render('templates/edit', [
            'template' => $template,
            'categories' => ['Site institucional', 'Landing page', 'Loja virtual', 'Portfólio', 'Site personalizado', 'Outros'],
        ]);
    }

    public function update(UpdateProjectTemplateRequest $request, ProjectTemplate $template)
    {
        $data = $request->validated();
        $phases = $data['phases'] ?? null;

        DB::transaction(function () use ($request, $template, $data, $phases) {
            $requestedVersion = $data['version'] ?? null;
            $nextVersion = $this->incrementVersion($template->version);

            if ($requestedVersion && $this->isVersionHigher($requestedVersion, $template->version)) {
                $version = $requestedVersion;
            } else {
                $version = $nextVersion;
            }

            $template->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'category' => $data['category'],
                'version' => $version,
                'is_active' => $data['is_active'] ?? $template->is_active,
                'updated_by' => $request->user()->id,
            ]);

            if ($phases !== null) {
                $template->phases()->delete();
                foreach (array_values($phases) as $pi => $phase) {
                    $phaseModel = $template->phases()->create([
                        'name' => $phase['name'],
                        'description' => $phase['description'] ?? null,
                        'order' => $phase['order'] ?? ($pi + 1),
                    ]);
                    foreach (array_values($phase['tasks'] ?? []) as $ti => $task) {
                        $phaseModel->tasks()->create([
                            'title' => $task['title'],
                            'description' => $task['description'] ?? null,
                            'priority' => $task['priority'] ?? 'medium',
                            'suggested_role' => $task['suggested_role'] ?? null,
                            'estimated_days' => $task['estimated_days'] ?? null,
                            'order' => $task['order'] ?? ($ti + 1),
                            'is_required' => $task['is_required'] ?? true,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('templates.show', $template)->with('success', 'Template atualizado.');
    }

    public function toggleActive(ProjectTemplate $template)
    {
        $this->authorize('update', $template);
        $template->update(['is_active' => ! $template->is_active, 'updated_by' => request()->user()->id]);
        return redirect()->route('templates.index')->with('success', $template->is_active ? 'Template ativado.' : 'Template inativado.');
    }

    public function previewTemplate(ProjectTemplate $template)
    {
        $template->load('phases.tasks');
        return response()->json(['template' => $template]);
    }

    protected function incrementVersion(string $current): string
    {
        $parts = explode('.', $current);
        $parts = array_map(fn ($p) => is_numeric($p) ? (int) $p : 0, $parts);
        if (count($parts) === 0) {
            return '1.0';
        }
        if (count($parts) === 1) {
            return $parts[0] . '.1';
        }
        $last = count($parts) - 1;
        $parts[$last]++;

        return implode('.', $parts);
    }

    protected function isVersionHigher(string $candidate, string $current): bool
    {
        $c = explode('.', $candidate);
        $cur = explode('.', $current);
        $len = max(count($c), count($cur));
        for ($i = 0; $i < $len; $i++) {
            $ci = isset($c[$i]) && is_numeric($c[$i]) ? (int) $c[$i] : 0;
            $ui = isset($cur[$i]) && is_numeric($cur[$i]) ? (int) $cur[$i] : 0;
            if ($ci > $ui) return true;
            if ($ci < $ui) return false;
        }

        return false;
    }
}
