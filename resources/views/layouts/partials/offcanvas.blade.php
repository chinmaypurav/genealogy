<x-ts-slide id="offcanvas" left size="sm" blur>
    @php
        $user        = auth()->user();
        $currentTeam = $user?->currentTeam;
        $role        = $user?->teamRole($currentTeam);
        $permissions = $user?->teamPermissions($currentTeam);
    @endphp

    <x-slot:title>{{ __('app.menu') }}</x-slot:title>

    {{-- role and permissions --}}
    <div class="pb-4">
        <div class="bg-secondary-100 text-secondary-800 rounded-sm p-4 text-base" role="alert">
            <div class="flex flex-row">
                <div class="basis-1/2">
                    {{ __('auth.role') }} :

                    <x-hr.narrow class="my-1 h-1 w-full rounded-sm border-0 bg-gray-100 max-md:mx-auto dark:bg-gray-700" />

                    {{ __('auth.permissions') }} :
                </div>

                <div class="basis-1/2">
                    @auth
                        {{ $role?->name ?? __('auth.guest') }}

                        <x-hr.narrow class="my-1 h-1 w-full rounded-sm border-0 bg-gray-100 max-md:mx-auto dark:bg-gray-700" />

                        @if (! empty($permissions))
                            @foreach ($permissions as $permission)
                                {{ $permission }}<br
                                 />
                            @endforeach
                        @else
                            <span class="text-gray-500 italic">{{ __('auth.no_permissions') }}</span>
                        @endif
                    @else
                        {{ __('auth.guest') }}

                        <x-hr.narrow class="my-1 h-1 w-full rounded-sm border-0 bg-gray-100 max-md:mx-auto dark:bg-gray-700" />
                    @endauth
                </div>
            </div>
        </div>
    </div>

    {{-- offcanvas menu --}}
    <div class="grow overflow-y-auto">
        @auth
            <div class="text-yellow-500 dark:text-yellow-200">
                {{ $role?->name ?? __('auth.role_unknown') }} ...
            </div>

            <x-hr.narrow />
            <x-nav-link-responsive href="{{ route('team') }}" :active="request()->routeIs('team')">
                {{ __('team.team') }}
            </x-nav-link-responsive>
            <x-nav-link-responsive href="{{ route('teamlog') }}" :active="request()->routeIs('teamlog')">
                {{ __('app.team_logbook') }}
            </x-nav-link-responsive>
            <x-nav-link-responsive href="{{ route('peoplelog') }}" :active="request()->routeIs('peoplelog')">
                {{ __('app.people_logbook') }}
            </x-nav-link-responsive>

            {{-- common links --}}
            <x-hr.narrow />
            <x-nav-link-responsive href="{{ route('help') }}" :active="request()->routeIs('help')">
                {{ __('app.help') }}
            </x-nav-link-responsive>
        @else
            {{-- guest --}}
            <div class="text-yellow-500 dark:text-yellow-200">{{ __('auth.guest') }} ...</div>

            <x-hr.narrow />
            <x-nav-link-responsive href="{{ route('help') }}" :active="request()->routeIs('help')">
                {{ __('app.help') }}
            </x-nav-link-responsive>
        @endauth
    </div>
</x-ts-slide>
