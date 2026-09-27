<?php

declare(strict_types=1);

use App\Actions\Users\DeleteUser;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public bool $confirmingUserDeletion = false;

    public string $password = '';

    public function confirmUserDeletion(): void
    {
        $this->resetErrorBag();

        $this->password = '';

        $this->dispatch('confirming-delete-user');

        $this->confirmingUserDeletion = true;
    }

    public function deleteUser(): void
    {
        $this->resetErrorBag();

        if (! Hash::check($this->password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'password' => [__('This password does not match our records.')],
            ]);
        }

        app(DeleteUser::class)->delete(Auth::user()->fresh());

        app(StatefulGuard::class)->logout();

        if (request()->hasSession()) {
            session()->invalidate();
            session()->regenerateToken();
        }

        $this->redirect(config('fortify.redirects.logout') ?? '/');
    }
};
