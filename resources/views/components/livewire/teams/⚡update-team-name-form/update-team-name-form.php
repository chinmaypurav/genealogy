<?php

declare(strict_types=1);

use App\Actions\Teams\UpdateTeamName;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    /** @var array<string, mixed> */
    public array $state = [];

    public function mount(Team $team): void
    {
        $this->team  = $team;
        $this->state = $team->withoutRelations()->toArray();
    }

    public function updateTeamName(): void
    {
        $this->resetErrorBag();

        app(UpdateTeamName::class)->update(Auth::user(), $this->team, $this->state);

        $this->dispatch('saved');
        $this->dispatch('refresh-navigation-menu');
    }
};
