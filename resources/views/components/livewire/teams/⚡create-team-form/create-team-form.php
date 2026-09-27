<?php

declare(strict_types=1);

use App\Actions\Teams\CreateTeam;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    /** @var array<string, string> */
    public array $state = [];

    public function createTeam(): void
    {
        $this->resetErrorBag();

        app(CreateTeam::class)->create(Auth::user(), $this->state);

        $this->redirect(config('fortify.home'));
    }
};
