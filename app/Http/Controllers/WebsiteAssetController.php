<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDomainRequest;
use App\Http\Requests\StoreEnvironmentRequest;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Http\Requests\StorePublicationRequest;
use App\Http\Requests\StoreRepositoryRequest;
use App\Models\Hosting;
use App\Models\MaintenanceRecord;
use App\Models\Task;
use App\Models\Website;
use App\Models\WebsiteDomain;
use App\Models\WebsiteEnvironment;
use App\Models\WebsitePublication;
use App\Models\WebsiteRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebsiteAssetController extends Controller
{
    public function storeDomain(StoreDomainRequest $request): WebsiteDomain
    {
        return throw new \BadMethodCallException('Use Laravel route model binding in dedicated actions.');
    }

    public function createDomain(Website $website)
    {
        $this->authorize('update', $website);
        $data = request()->validate([
            'domain' => ['required', 'string', 'max:255'],
            'is_primary' => ['sometimes', 'boolean'],
            'registrar' => ['nullable', 'string', 'max:100'],
            'registered_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['required', 'in:ativo,suspenso,transferido,expirado'],
            'dns_notes' => ['nullable', 'string'],
        ]);
        $normalized = mb_strtolower(trim($data['domain']));
        if (WebsiteDomain::where('domain', $normalized)->whereNull('deleted_at')->exists()) {
            abort(422, 'Este domínio já está em uso por outro site.');
        }

        return DB::transaction(function () use ($website, $data, $normalized) {
            $isPrimary = (bool) ($data['is_primary'] ?? false);
            if ($isPrimary) {
                $website->domains()->where('is_primary', true)->update(['is_primary' => false]);
            }
            return $website->domains()->create([
                'domain' => $normalized,
                'is_primary' => $isPrimary,
                'registrar' => $data['registrar'] ?? null,
                'registered_at' => $data['registered_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'status' => $data['status'],
                'dns_notes' => $data['dns_notes'] ?? null,
            ]);
        });
    }

    public function createEnvironment(Website $website)
    {
        $this->authorize('update', $website);
        $data = request()->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:development,staging,production'],
            'url' => ['nullable', 'url', 'max:255'],
            'hosting_id' => ['nullable', 'uuid', 'exists:hostings,id'],
            'status' => ['required', 'in:ativo,suspenso,offline,desativado'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($website, $data) {
            if ($data['type'] === 'production' && $data['status'] === 'ativo') {
                $exists = $website->environments()
                    ->where('type', 'production')
                    ->where('status', 'ativo')
                    ->whereNull('deleted_at')
                    ->exists();
                if ($exists) {
                    abort(422, 'Já existe ambiente de produção ativo para este site.');
                }
            }
            return $website->environments()->create($data);
        });
    }

    public function createRepository(Website $website)
    {
        $this->authorize('update', $website);
        $data = request()->validate([
            'provider' => ['nullable', 'in:github,gitlab,bitbucket,outros'],
            'name' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'default_branch' => ['nullable', 'string', 'max:100'],
            'environment_id' => ['nullable', 'uuid', 'exists:website_environments,id'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($data['environment_id'] ?? null) {
            $env = WebsiteEnvironment::find($data['environment_id']);
            if (! $env || (string) $env->website_id !== (string) $website->id) {
                abort(422, 'O ambiente não pertence a este site.');
            }
        }
        if (WebsiteRepository::where('url', $data['url'])->whereNull('deleted_at')->exists()) {
            abort(422, 'Este repositório já está cadastrado.');
        }

        return $website->repositories()->create($data);
    }

    public function storePublication(StorePublicationRequest $request, Website $website)
    {
        $this->authorize('publish', $website);
        $data = $request->validated();
        $env = WebsiteEnvironment::findOrFail($data['environment_id']);
        if ((string) $env->website_id !== (string) $website->id) {
            abort(422, 'O ambiente não pertence a este site.');
        }

        $publication = DB::transaction(function () use ($website, $data, $env) {
            $pub = $website->publications()->create([
                'environment_id' => $data['environment_id'],
                'version' => $data['version'] ?? null,
                'published_at' => $data['published_at'],
                'responsible_id' => $data['responsible_id'] ?? request()->user()->id,
                'description' => $data['description'] ?? null,
                'commit_reference' => $data['commit_reference'] ?? null,
                'result' => $data['result'],
                'notes' => $data['notes'] ?? null,
            ]);

            if ($data['result'] === 'sucesso' && $env->type === 'production') {
                $website->update(['published_at' => now()->toDateString(), 'status' => 'publicado']);
            }

            return $pub;
        });

        return redirect()->route('sites.show', $website)->with('success', 'Publicação registrada.');
    }

    public function storeMaintenance(StoreMaintenanceRequest $request, Website $website)
    {
        $this->authorize('maintenance', $website);
        $data = $request->validated();

        if ($data['project_id'] ?? null) {
            $project = \App\Models\Project::find($data['project_id']);
            if (! $project || (string) $project->client_id !== (string) $website->client_id) {
                abort(422, 'O projeto não pertence ao cliente deste site.');
            }
        }
        if ($data['task_id'] ?? null) {
            $task = Task::find($data['task_id']);
            if (! $task) abort(422, 'Tarefa não encontrada.');
            if ($data['project_id'] && (string) $task->project_id !== (string) $data['project_id']) {
                abort(422, 'A tarefa não pertence ao projeto informado.');
            }
            if (! $data['project_id'] && (string) $task->project->client_id !== (string) $website->client_id) {
                abort(422, 'A tarefa não pertence ao cliente deste site.');
            }
        }

        $record = $website->maintenanceRecords()->create($data + [
            'created_by' => $request->user()->id,
            'opened_at' => $data['opened_at'] ?? now()->toDateString(),
        ]);

        if ($record->status === 'aberta' || $record->status === 'em_andamento') {
            $website->update(['status' => 'manutencao']);
        }

        return redirect()->route('sites.show', $website)->with('success', 'Registro de manutenção criado.');

    }
}
