@php

    $user = auth()->user();

    $initials = collect(explode(' ', $user->name ?? 'U'))->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('');

@endphp

<div class="topbar">

    <div class="topbar-title">@yield('pageTitle', 'Dashboard')</div>

    <div class="topbar-right">

        @yield('topbarExtra')

        <a href="{{ route('profile.edit') }}" class="icon-btn" title="Settings"><i class="ti ti-settings"></i></a>

        <div class="profile-menu" id="profile-menu">

            <button type="button" class="avatar profile-trigger" aria-expanded="false" aria-haspopup="true" title="{{ $user->name }}">

                {{ $initials }}

            </button>

            <div class="profile-dropdown" hidden>

                <div class="profile-dropdown-head">

                    <div class="profile-dropdown-name">{{ $user->name }}</div>

                    <div class="profile-dropdown-email">{{ $user->email }}</div>

                </div>

                <a href="{{ route('profile.edit') }}" class="profile-dropdown-item">

                    <i class="ti ti-user"></i> Profile

                </a>

                <form method="POST" action="{{ route('logout') }}" class="profile-dropdown-form">

                    @csrf

                    <button type="submit" class="profile-dropdown-item profile-dropdown-logout">

                        <i class="ti ti-logout"></i> Logout

                    </button>

                </form>

            </div>

        </div>

    </div>

</div>



@once

    @push('scripts')

    <script>

    document.addEventListener('DOMContentLoaded', function () {

        document.querySelectorAll('.profile-menu').forEach(function (menu) {

            const trigger = menu.querySelector('.profile-trigger');

            const dropdown = menu.querySelector('.profile-dropdown');

            if (!trigger || !dropdown) return;



            const open = () => {

                dropdown.hidden = false;

                trigger.setAttribute('aria-expanded', 'true');

            };



            const close = () => {

                dropdown.hidden = true;

                trigger.setAttribute('aria-expanded', 'false');

            };



            trigger.addEventListener('click', (e) => {

                e.stopPropagation();

                dropdown.hidden ? open() : close();

            });



            document.addEventListener('click', (e) => {

                if (!menu.contains(e.target)) close();

            });



            document.addEventListener('keydown', (e) => {

                if (e.key === 'Escape') close();

            });

        });

    });

    </script>

    @endpush

@endonce

