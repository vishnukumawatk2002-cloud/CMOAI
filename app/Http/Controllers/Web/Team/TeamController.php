<?php

namespace App\Http\Controllers\Web\Team;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $brands = $user->brands()->orderBy('name')->pluck('name');
        $brandLabel = $brands->isEmpty()
            ? 'No brands yet'
            : ($brands->count() <= 3 ? $brands->join(', ') : 'All brands');

        $members = collect([
            [
                'name' => $user->full_name,
                'email' => $user->email,
                'brands' => $brandLabel,
                'role' => 'Admin',
                'role_class' => 'r-admin',
                'color' => '#5B4FC9',
                'initials' => $user->initials,
                'is_self' => true,
            ],
        ]);

        $permissions = [
            ['label' => 'View all brands', 'admin' => 'check', 'manager' => 'text:Own clients only', 'creator' => 'text:Assigned brands'],
            ['label' => 'Create & edit posts', 'admin' => 'check', 'manager' => 'check', 'creator' => 'check'],
            ['label' => 'Approve & publish posts', 'admin' => 'check', 'manager' => 'check', 'creator' => 'x'],
            ['label' => 'Connect social accounts', 'admin' => 'check', 'manager' => 'check', 'creator' => 'x'],
            ['label' => 'View analytics', 'admin' => 'check', 'manager' => 'check', 'creator' => 'text:Limited'],
            ['label' => 'Download reports', 'admin' => 'check', 'manager' => 'check', 'creator' => 'x'],
            ['label' => 'Invite team & clients', 'admin' => 'check', 'manager' => 'text:Clients only', 'creator' => 'x'],
            ['label' => 'Manage billing', 'admin' => 'check', 'manager' => 'x', 'creator' => 'x'],
        ];

        return view('app.team.index', [
            'members' => $members,
            'permissions' => $permissions,
            'brandOptions' => $brands,
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:admin,account_manager,content_creator,client_viewer'],
        ]);

        return back()->with('success', 'Invite sent to '.$request->email.'. They will receive an email to join your team.');
    }
}
