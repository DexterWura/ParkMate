<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Settings\Models\AppSetting;
use App\Domains\Settings\Services\SettingsCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class SettingsAdminController extends Controller
{
    public function edit(SettingsCatalog $catalog): View
    {
        $catalog->ensureDefaults();
        $settings = AppSetting::query()->orderBy('group')->orderBy('key')->get()->groupBy('group');
        $help = [];
        foreach ($catalog->definitions() as $definition) {
            $help[$definition['key']] = $definition['help'] ?? null;
        }

        return view('admin.settings.edit', compact('settings', 'help'));
    }

    public function update(Request $request): RedirectResponse
    {
        $payload = $request->input('settings', []);
        if (! is_array($payload)) {
            return back()->withErrors(['settings' => 'Invalid settings payload.']);
        }

        foreach ($payload as $key => $value) {
            $setting = AppSetting::query()->find($key);
            if ($setting === null) {
                continue;
            }
            $value = is_string($value) ? trim($value) : $value;
            if ($setting->is_secret && ($value === null || $value === '')) {
                continue;
            }
            AppSetting::putValue($key, $value === '' ? null : (string) $value);
        }

        return back()->with('status', 'API and app settings saved.');
    }
}
