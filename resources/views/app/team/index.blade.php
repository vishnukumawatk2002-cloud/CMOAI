@extends('layouts.app')

@section('title', 'Team — CMO AI')
@section('pageTitle', 'Team')

@section('topbarExtra')
    <button type="button" class="btn btn-purple btn-sm" data-open-invite-modal><i class="ti ti-user-plus"></i> Invite member</button>
@endsection

@section('content')
<div class="mgmt-page">
    <div class="mgmt-head">
        <div>
            <h1 class="mgmt-title">Team</h1>
            <p class="mgmt-sub">Manage roles, assignments, and access</p>
        </div>
        <button type="button" class="btn btn-purple btn-sm mgmt-head-btn" data-open-invite-modal><i class="ti ti-user-plus"></i> Invite member</button>
    </div>

    <div class="card">
        <div class="card-title">Team members ({{ $members->count() }})</div>
        @foreach ($members as $member)
            <div class="team-row">
                <div class="team-avatar" style="background:{{ $member['color'] }}">{{ $member['initials'] }}</div>
                <div class="team-info">
                    <div class="team-name">{{ $member['name'] }}{{ ($member['is_self'] ?? false) ? ' (You)' : '' }}</div>
                    <div class="team-meta">{{ $member['email'] }} · {{ $member['brands'] }}</div>
                </div>
                <span class="role-badge {{ $member['role_class'] }}">{{ $member['role'] }}</span>
                <button type="button" class="btn btn-ghost btn-sm" disabled>Manage</button>
            </div>
        @endforeach
    </div>

    <div class="card" style="margin-top:16px">
        <div class="card-title">Role permissions</div>
        <div class="mgmt-table-wrap">
            <table class="mgmt-table">
                <thead>
                    <tr>
                        <th>Permission</th>
                        <th>Admin</th>
                        <th>Account Manager</th>
                        <th>Content Creator</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($permissions as $perm)
                        <tr>
                            <td>{{ $perm['label'] }}</td>
                            @foreach (['admin', 'manager', 'creator'] as $col)
                                <td>
                                    @if (($perm[$col] ?? '') === 'check')
                                        <i class="ti ti-check perm-yes"></i>
                                    @elseif (($perm[$col] ?? '') === 'x')
                                        <i class="ti ti-x perm-no"></i>
                                    @else
                                        <span class="perm-muted">{{ str_replace('text:', '', $perm[$col]) }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('modals')
<div class="modal-overlay" id="invite-modal" aria-hidden="true">
    <div class="cmo-modal" role="dialog" aria-modal="true" aria-labelledby="invite-modal-title">
        <div class="modal-head">
            <div>
                <h2 id="invite-modal-title">Invite Team Member</h2>
                <p>Send an invite to collaborate on your brands.</p>
            </div>
            <button type="button" class="close-btn" data-close-invite-modal aria-label="Close"><i class="ti ti-x"></i></button>
        </div>
        <form method="POST" action="{{ route('app.team.invite') }}">
            @csrf
            <div class="modal-body">
                <div class="mfield">
                    <label for="invite-name">Full name</label>
                    <input type="text" id="invite-name" name="name" value="{{ old('name') }}" placeholder="e.g. Sakshi Iyer" required>
                    @error('name')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="mfield">
                    <label for="invite-email">Email</label>
                    <input type="email" id="invite-email" name="email" value="{{ old('email') }}" placeholder="sakshi@example.com" required>
                    @error('email')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                <div class="mfield">
                    <label for="invite-role">Role</label>
                    <select id="invite-role" name="role" class="mfield-select" required>
                        <option value="admin" @selected(old('role') === 'admin')>Admin — Full access to all brands</option>
                        <option value="account_manager" @selected(old('role') === 'account_manager')>Account Manager — Assigned brands only</option>
                        <option value="content_creator" @selected(old('role') === 'content_creator')>Content Creator — Create & submit posts</option>
                        <option value="client_viewer" @selected(old('role') === 'client_viewer')>Client Viewer — View & approve only</option>
                    </select>
                    @error('role')<span class="field-error">{{ $message }}</span>@enderror
                </div>
                @if ($brandOptions->isNotEmpty())
                <div class="mfield">
                    <label for="invite-brands">Assign to brands</label>
                    <select id="invite-brands" name="brands[]" class="mfield-select" multiple size="{{ min(5, $brandOptions->count()) }}">
                        @foreach ($brandOptions as $brandName)
                            <option value="{{ $brandName }}">{{ $brandName }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            <div class="modal-foot" style="justify-content:flex-end">
                <button type="button" class="btn btn-ghost" data-close-invite-modal>Cancel</button>
                <button type="submit" class="btn btn-purple"><i class="ti ti-send"></i> Send invite</button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('invite-modal');
    if (!modal) return;

    const open = () => {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    };

    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    document.querySelectorAll('[data-open-invite-modal]').forEach((btn) => btn.addEventListener('click', open));
    document.querySelectorAll('[data-close-invite-modal]').forEach((btn) => btn.addEventListener('click', close));
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('is-open')) close(); });

    @if ($errors->has('name') || $errors->has('email') || $errors->has('role'))
    open();
    @endif
});
</script>
@endpush
