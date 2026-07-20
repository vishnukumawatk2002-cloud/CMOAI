<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingUpdateRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::query()
            ->orderBy('group')
            ->orderBy('label')
            ->get()
            ->groupBy('group');

        return view('admin.settings.index', compact('settings'));
    }

    public function update(SettingUpdateRequest $request): RedirectResponse
    {
        foreach ($request->input('settings', []) as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('status', 'Settings saved successfully.');
    }
}
