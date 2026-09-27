<?php

declare(strict_types=1);

use App\Support\Agent;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public bool $confirmingLogout = false;

    public string $password = '';

    public function confirmLogout(): void
    {
        $this->password = '';

        $this->dispatch('confirming-logout-other-browser-sessions');

        $this->confirmingLogout = true;
    }

    /**
     * Sessions can only be listed and revoked when they are stored in the database.
     */
    public function logoutOtherBrowserSessions(): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $this->resetErrorBag();

        if (! Hash::check($this->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => [__('This password does not match our records.')],
            ]);
        }

        app(StatefulGuard::class)->logoutOtherDevices($this->password);

        $this->sessionsQuery()->where('id', '!=', session()->getId())->delete();

        // logoutOtherDevices() rehashed the password; keep this session's stored hash in sync.
        session()->put(['password_hash_' . Auth::getDefaultDriver() => Auth::user()->getAuthPassword()]);

        $this->confirmingLogout = false;

        $this->dispatch('loggedOut');
    }

    /**
     * @return Collection<int, object{agent: Agent, ip_address: string|null, is_current_device: bool, last_active: string}>
     */
    #[Computed]
    public function sessions(): Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return $this->sessionsQuery()
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn (object $session): object => (object) [
                'agent'             => tap(new Agent(), fn (Agent $agent) => $agent->setUserAgent($session->user_agent)),
                'ip_address'        => $session->ip_address,
                'is_current_device' => $session->id === session()->getId(),
                'last_active'       => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
            ]);
    }

    /**
     * The sessions table has no model; it is owned by Laravel's database session driver.
     */
    protected function sessionsQuery(): Builder
    {
        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', Auth::id());
    }
};
