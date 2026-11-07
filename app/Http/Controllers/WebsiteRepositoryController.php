<?php

namespace App\Http\Controllers;

use App\Domain\Websites\Actions\SyncWebsiteRepositoryAction;
use App\Models\Website;
use App\Models\WebsiteRepository;

class WebsiteRepositoryController extends Controller
{
    public function sync(Website $website, WebsiteRepository $repository, SyncWebsiteRepositoryAction $action)
    {
        if ((string) $repository->website_id !== (string) $website->id) {
            abort(404);
        }

        $this->authorize('update', $website);

        try {
            $repository = $action($repository);
        } catch (\Throwable $e) {
            $repository->forceFill([
                'last_sync_attempt_at' => now(),
                'last_sync_status' => 'api_error',
                'last_sync_error' => 'Falha inesperada na sincronização. Tente novamente.',
            ])->save();

            return back()->with('error', 'Falha inesperada na sincronização. Tente novamente.');
        }

        if ($repository->last_sync_status === 'ok') {
            return back()->with('success', 'Repositório sincronizado com o GitHub.');
        }

        return back()->with('error', $repository->last_sync_error ?? 'Falha na sincronização.');
    }
}
