<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    /** @var array<string, mixed> */
    public array $state = [];

    /** @var TemporaryUploadedFile|null */
    public $photo;

    public bool $verificationLinkSent = false;

    public function mount(): void
    {
        $this->state = array_merge(['email' => $this->user->email], $this->user->withoutRelations()->toArray());
    }

    public function updateProfileInformation(): void
    {
        $this->resetErrorBag();

        app(UpdatesUserProfileInformation::class)->update(
            $this->user,
            $this->photo ? array_merge($this->state, ['photo' => $this->photo]) : $this->state
        );

        // A new photo changes URLs across the page, so reload it instead of patching the form.
        if (isset($this->photo)) {
            $this->redirectRoute('profile.show');

            return;
        }

        $this->dispatch('saved');
        $this->dispatch('refresh-navigation-menu');
    }

    public function deleteProfilePhoto(): void
    {
        $this->user->deleteProfilePhoto();

        $this->dispatch('refresh-navigation-menu');
    }

    public function sendEmailVerification(): void
    {
        $this->user->sendEmailVerificationNotification();

        $this->verificationLinkSent = true;
    }

    #[Computed]
    public function user(): User
    {
        return Auth::user();
    }
};
