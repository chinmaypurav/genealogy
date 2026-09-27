<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    /** @var array{current_password: string, password: string, password_confirmation: string} */
    public array $state = [
        'current_password'      => '',
        'password'              => '',
        'password_confirmation' => '',
    ];

    public function updatePassword(): void
    {
        $this->resetErrorBag();

        app(UpdatesUserPasswords::class)->update($this->user, $this->state);

        // Keep this session valid: AuthenticateSession logs out sessions whose stored hash no longer matches.
        if (request()->hasSession()) {
            session()->put(['password_hash_' . Auth::getDefaultDriver() => $this->user->getAuthPassword()]);
        }

        $this->reset('state');

        $this->dispatch('saved');
    }

    #[Computed]
    public function user(): User
    {
        return Auth::user();
    }
};
