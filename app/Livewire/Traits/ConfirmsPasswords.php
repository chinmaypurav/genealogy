<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmPassword;

/**
 * Backs the <x-confirms-password> Blade component: asks for the password before a sensitive action.
 *
 * Copied from Jetstream. The component calls startConfirmingPassword() and then runs its
 * wire:then action once "password-confirmed" is dispatched with the matching id. Actions must still
 * call ensurePasswordIsConfirmed(), because Livewire methods can be called directly.
 */
trait ConfirmsPasswords
{
    public bool $confirmingPassword = false;

    public ?string $confirmableId = null;

    public string $confirmablePassword = '';

    public function startConfirmingPassword(string $confirmableId): void
    {
        $this->resetErrorBag();

        if ($this->passwordIsConfirmed()) {
            $this->dispatch('password-confirmed', id: $confirmableId);

            return;
        }

        $this->confirmingPassword  = true;
        $this->confirmableId       = $confirmableId;
        $this->confirmablePassword = '';

        $this->dispatch('confirming-password');
    }

    public function stopConfirmingPassword(): void
    {
        $this->confirmingPassword  = false;
        $this->confirmableId       = null;
        $this->confirmablePassword = '';
    }

    public function confirmPassword(): void
    {
        if (! app(ConfirmPassword::class)(app(StatefulGuard::class), Auth::user(), $this->confirmablePassword)) {
            throw ValidationException::withMessages([
                'confirmable_password' => [__('This password does not match our records.')],
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->dispatch('password-confirmed', id: $this->confirmableId);

        $this->stopConfirmingPassword();
    }

    protected function ensurePasswordIsConfirmed(): void
    {
        abort_unless($this->passwordIsConfirmed(), 403);
    }

    protected function passwordIsConfirmed(): bool
    {
        return (time() - session('auth.password_confirmed_at', 0)) < config('auth.password_timeout', 900);
    }
}
