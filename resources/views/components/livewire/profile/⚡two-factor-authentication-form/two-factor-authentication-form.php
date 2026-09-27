<?php

declare(strict_types=1);

use App\Livewire\Traits\ConfirmsPasswords;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Laravel\Fortify\Features;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    use ConfirmsPasswords;

    public bool $showingQrCode = false;

    public bool $showingConfirmation = false;

    public bool $showingRecoveryCodes = false;

    public ?string $code = null;

    /**
     * An unconfirmed setup from an abandoned visit would otherwise look enabled, so start over.
     */
    public function mount(): void
    {
        if ($this->requiresConfirmation() && is_null($this->user->two_factor_confirmed_at)) {
            app(DisableTwoFactorAuthentication::class)($this->user);
        }
    }

    public function enableTwoFactorAuthentication(): void
    {
        $this->ensurePasswordIsConfirmedIfRequired();

        app(EnableTwoFactorAuthentication::class)($this->user);

        $this->showingQrCode        = true;
        $this->showingConfirmation  = $this->requiresConfirmation();
        $this->showingRecoveryCodes = ! $this->requiresConfirmation();
    }

    public function confirmTwoFactorAuthentication(): void
    {
        $this->ensurePasswordIsConfirmedIfRequired();

        app(ConfirmTwoFactorAuthentication::class)($this->user, $this->code);

        $this->showingQrCode        = false;
        $this->showingConfirmation  = false;
        $this->showingRecoveryCodes = true;
    }

    public function showRecoveryCodes(): void
    {
        $this->ensurePasswordIsConfirmedIfRequired();

        $this->showingRecoveryCodes = true;
    }

    public function regenerateRecoveryCodes(): void
    {
        $this->ensurePasswordIsConfirmedIfRequired();

        app(GenerateNewRecoveryCodes::class)($this->user);

        $this->showingRecoveryCodes = true;
    }

    public function disableTwoFactorAuthentication(): void
    {
        $this->ensurePasswordIsConfirmedIfRequired();

        app(DisableTwoFactorAuthentication::class)($this->user);

        $this->showingQrCode        = false;
        $this->showingConfirmation  = false;
        $this->showingRecoveryCodes = false;
    }

    #[Computed]
    public function user(): User
    {
        return Auth::user();
    }

    #[Computed]
    public function enabled(): bool
    {
        return ! empty($this->user->two_factor_secret);
    }

    protected function requiresConfirmation(): bool
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }

    protected function ensurePasswordIsConfirmedIfRequired(): void
    {
        if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
            $this->ensurePasswordIsConfirmed();
        }
    }
};
