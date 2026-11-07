<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        return Inertia::render('settings/index', [
            'settings' => array_merge([
                'company_name' => 'WebStudio OS',
                'company_email' => 'contato@webstudio.com',
                'notifications_enabled' => true,
                'maintenance_mode' => false,
            ], $settings),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'notifications_enabled' => 'nullable|boolean',
            'maintenance_mode' => 'nullable|boolean',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return back()->with('success', 'Configurações salvas com sucesso.');
    }
}
