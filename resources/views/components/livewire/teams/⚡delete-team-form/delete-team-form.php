<?php

declare(strict_types=1);

use App\Actions\Teams\DeleteTeam;
use App\Models\Team;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

new class extends Component
{
    public Team $team;

    public bool $confirmingTeamDeletion = false;

    /**
     * Personal teams are refused here rather than in DeleteTeam, because deleting a user
     * account must still be able to delete their personal team.
     */
    public function deleteTeam(): void
    {
        Gate::authorize('delete', $this->team);

        if ($this->team->personal_team) {
            throw ValidationException::withMessages([
                'team' => __('You may not delete your personal team.'),
            ])->errorBag('deleteTeam');
        }

        app(DeleteTeam::class)->delete($this->team);

        $this->redirect(config('fortify.home'));
    }
};
