<?php

namespace App\Http\Controllers;

use App\Domain\Demands\Actions\TransitionDemandAction;
use App\Enums\DemandStatus;
use App\Http\Requests\StoreDemandRequest;
use App\Http\Requests\UpdateDemandRequest;
use App\Models\Client;
use App\Models\Demand;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DemandController extends Controller
{
    public function index(Request $request)
    {
        $query = Demand::query()
            ->with(['client:id,name', 'opportunity:id,title', 'commercialOwner:id,name', 'technicalOwner:id,name'])
            ->orderByDesc('created_at');

        $user = $request->user();
        if (! $user->hasRole('OWNER')) {
            if ($user->hasRole('DEVELOPER')) {
                $query->where(function ($q) use ($user) {
                    $q->where('technical_owner_id', $user->id)
                        ->orWhereExists(function ($qq) use ($user) {
                            $qq->selectRaw(1)->from('projects')
                                ->whereColumn('projects.demand_id', 'demands.id')
                                ->where(function ($p) use ($user) {
                                    $p->where('projects.technical_owner_id', $user->id)
                                        ->orWhereExists(fn ($m) => $m->selectRaw(1)->from('project_members')->whereColumn('project_members.project_id', 'projects.id')->where('project_members.user_id', $user->id))
                                        ->orWhereExists(fn ($t) => $t->selectRaw(1)->from('tasks')->whereColumn('tasks.project_id', 'projects.id')->where('tasks.assignee_id', $user->id));
                                });
                        });
                });
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('commercial_owner_id', $user->id)
                        ->orWhere('created_by', $user->id);
                });
            }
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhereHas('client', fn ($qq) => $qq->where('name', 'like', "%{$s}%"));
            });
        }
        if ($request->status) $query->where('status', $request->status);
        if ($request->client_id) $query->where('client_id', $request->client_id);
        if ($request->responsible_id) $query->where('commercial_owner_id', $request->responsible_id);
        if ($request->period) {
            $period = $request->period;
            $query->when($period === '7d', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)));
            $query->when($period === '30d', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)));
            $query->when($period === '90d', fn ($q) => $q->where('created_at', '>=', now()->subDays(90)));
        }

        return Inertia::render('demands/index', [
            'demands' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'client_id', 'responsible_id', 'period']),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return Inertia::render('demands/create', [
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'opportunities' => Opportunity::orderByDesc('created_at')->limit(100)->get(['id', 'title', 'client_id']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreDemandRequest $request)
    {
        $data = $request->validated();
        $briefingData = $data['briefing'] ?? [];
        $files = $data['files'] ?? [];

        $demand = DB::transaction(function () use ($request, $data, $briefingData, $files) {
            $demand = Demand::create([
                'title' => $data['title'],
                'client_id' => $data['client_id'],
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'commercial_owner_id' => $request->user()->id,
                'service_type' => $data['service_type'] ?? null,
                'description' => $data['description'] ?? null,
                'contracted_scope' => $data['contracted_scope'] ?? null,
                'negotiated_value' => $data['negotiated_value'] ?? null,
                'contracted_at' => $data['contracted_at'] ?? null,
                'desired_deadline' => $data['desired_deadline'] ?? null,
                'priority' => $data['priority'],
                'status' => DemandStatus::Draft->value,
                'internal_notes' => $data['internal_notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            if (! empty($briefingData)) {
                $demand->briefing()->create($briefingData + ['demand_id' => $demand->id]);
            } else {
                $demand->briefing()->create(['demand_id' => $demand->id]);
            }

            $demand->histories()->create([
                'user_id' => $request->user()->id,
                'event' => 'created',
                'description' => 'Demanda criada como rascunho.',
            ]);

            foreach ($files as $file) {
                $path = $file->store('demand-attachments', 'local');
                $demand->attachments()->create([
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }

            return $demand;
        });

        return redirect()->route('demands.show', $demand)->with('success', 'Demanda criada com sucesso.');
    }

    public function show(Demand $demand)
    {
        $this->authorize('view', $demand);
        $viewer = auth()->user();
        if ($viewer && $viewer->hasRole('DEVELOPER') && ! $viewer->hasRole('OWNER')) {
            $demand->negotiated_value = null;
            $demand->internal_notes = null;
        }
        $demand->load(['client', 'opportunity', 'commercialOwner:id,name', 'technicalOwner:id,name', 'creator:id,name', 'briefing', 'attachments.uploader:id,name', 'histories.user:id,name']);

        $statusEnum = DemandStatus::tryFrom($demand->status);
        $can = [
            'update' => auth()->user()?->can('update', $demand),
            'submit' => auth()->user()?->can('submit', $demand),
            'startReview' => auth()->user()?->can('startReview', $demand),
            'requestInformation' => auth()->user()?->can('requestInformation', $demand),
            'approve' => auth()->user()?->can('approve', $demand),
            'reject' => auth()->user()?->can('reject', $demand),
            'cancel' => auth()->user()?->can('cancel', $demand),
            'upload' => auth()->user()?->can('upload', $demand),
        ];

        return Inertia::render('demands/show', [
            'demand' => $demand,
            'statusHint' => $statusEnum?->nextActionHint() ?? '',
            'can' => $can,
        ]);
    }

    public function edit(Demand $demand)
    {
        $this->authorize('update', $demand);
        $demand->load('briefing');
        return Inertia::render('demands/edit', [
            'demand' => $demand,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'opportunities' => Opportunity::orderByDesc('created_at')->limit(100)->get(['id', 'title', 'client_id']),
            'users' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateDemandRequest $request, Demand $demand)
    {
        $this->authorize('update', $demand);
        $data = $request->validated();
        $briefingData = $data['briefing'] ?? null;
        $files = $data['files'] ?? [];

        DB::transaction(function () use ($request, $demand, $data, $briefingData, $files) {
            $demand->update([
                'title' => $data['title'],
                'client_id' => $data['client_id'],
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'service_type' => $data['service_type'] ?? null,
                'description' => $data['description'] ?? null,
                'contracted_scope' => $data['contracted_scope'] ?? null,
                'negotiated_value' => $data['negotiated_value'] ?? null,
                'contracted_at' => $data['contracted_at'] ?? null,
                'desired_deadline' => $data['desired_deadline'] ?? null,
                'priority' => $data['priority'],
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);

            if ($briefingData !== null) {
                $demand->briefing()->updateOrCreate(['demand_id' => $demand->id], $briefingData);
            }

            foreach ($files as $file) {
                $path = $file->store('demand-attachments', 'local');
                $demand->attachments()->create([
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }

            $demand->histories()->create([
                'user_id' => $request->user()->id,
                'event' => 'updated',
                'description' => 'Demanda atualizada.',
            ]);
        });

        return redirect()->route('demands.show', $demand)->with('success', 'Demanda atualizada.');
    }

    public function submit(Demand $demand, TransitionDemandAction $transition)
    {
        $this->authorize('submit', $demand);
        $demand->load('client', 'opportunity', 'briefing');
        $this->validateSubmittable($demand);

        $transition($demand, DemandStatus::Submitted, request()->user(), 'Demanda enviada para análise.');
        \App\Models\User::where('is_active', true)->whereHas('roles', fn ($q) => $q->where('name', 'OWNER'))->get()->each(fn ($o) => $o->notify(new \App\Notifications\DemandSubmitted($demand)));
        return redirect()->route('demands.show', $demand)->with('success', 'Demanda enviada para análise.');
    }

    public function startReview(Demand $demand, TransitionDemandAction $transition)
    {
        $this->authorize('startReview', $demand);
        $transition($demand, DemandStatus::UnderReview, request()->user(), 'Análise técnica iniciada.');
        return redirect()->route('demands.show', $demand)->with('success', 'Análise iniciada.');
    }

    public function requestInformation(Request $request, Demand $demand, TransitionDemandAction $transition)
    {
        $this->authorize('requestInformation', $demand);
        $request->validate(['reason' => 'required|string|max:1000']);
        $transition($demand, DemandStatus::NeedsInformation, $request->user(), 'Solicitação de complementação: '.$request->reason, ['reason' => $request->reason]);
        return redirect()->route('demands.show', $demand)->with('success', 'Solicitação de complementação enviada.');
    }

    public function approve(Request $request, Demand $demand, TransitionDemandAction $transition)
    {
        $this->authorize('approve', $demand);
        $request->validate(['notes' => 'nullable|string|max:1000']);
        $transition($demand, DemandStatus::Approved, $request->user(), 'Demanda aprovada.' . ($request->notes ? ' '.$request->notes : ''), ['notes' => $request->notes]);
        if ($demand->commercial_owner_id) {
            \App\Models\User::find($demand->commercial_owner_id)?->notify(new \App\Notifications\DemandStatusChanged($demand, 'Demanda aprovada'));
        }
        return redirect()->route('demands.show', $demand)->with('success', 'Demanda aprovada. Pronta para conversão em projeto (fase posterior).');
    }

    public function reject(Request $request, Demand $demand, TransitionDemandAction $transition)
    {
        $this->authorize('reject', $demand);
        $request->validate(['reason' => 'required|string|max:1000']);
        $transition($demand, DemandStatus::Rejected, $request->user(), 'Demanda recusada: '.$request->reason, ['reason' => $request->reason]);
        if ($demand->commercial_owner_id) {
            \App\Models\User::find($demand->commercial_owner_id)?->notify(new \App\Notifications\DemandStatusChanged($demand, 'Demanda recusada'));
        }
        return redirect()->route('demands.show', $demand)->with('success', 'Demanda recusada.');
    }

    public function cancel(Request $request, Demand $demand, TransitionDemandAction $transition)
    {
        $this->authorize('cancel', $demand);
        $request->validate(['reason' => 'required|string|max:1000']);
        $transition($demand, DemandStatus::Cancelled, $request->user(), 'Demanda cancelada: '.$request->reason, ['reason' => $request->reason]);
        return redirect()->route('demands.show', $demand)->with('success', 'Demanda cancelada.');
    }

    public function uploadAttachment(Request $request, Demand $demand)
    {
        $this->authorize('upload', $demand);
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,gif,doc,docx,xls,xlsx,txt,zip,mp4'],
        ]);
        $file = $request->file('file');
        $path = $file->store('demand-attachments', 'local');
        $demand->attachments()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);
        $demand->histories()->create([
            'user_id' => $request->user()->id,
            'event' => 'attachment_uploaded',
            'description' => 'Anexo enviado: '.$file->getClientOriginalName(),
        ]);
        return back()->with('success', 'Anexo enviado com sucesso.');
    }

    public function downloadAttachment(Demand $demand, $attachmentId)
    {
        $this->authorize('download', $demand);
        $attachment = $demand->attachments()->findOrFail($attachmentId);
        if (! Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404, 'Arquivo não encontrado.');
        }
        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
            'Content-Disposition' => 'attachment; filename="'.basename($attachment->original_name).'"',
        ]);
    }

    protected function validateSubmittable(Demand $demand): void
    {
        $errors = [];
        if (empty($demand->title)) $errors[] = 'Título';
        if (empty($demand->client_id)) $errors[] = 'Cliente';
        if (empty($demand->service_type)) $errors[] = 'Tipo de serviço';
        if (empty($demand->description)) $errors[] = 'Descrição';
        if ($errors) {
            abort(422, 'Campos obrigatórios para envio: '.implode(', ', $errors).'.');
        }
    }
}
