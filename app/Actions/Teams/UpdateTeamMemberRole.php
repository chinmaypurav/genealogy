<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Changes a team member's role.
 *
 * Kept as an action, like the other team membership changes, so authorization and role validation
 * live in one place instead of in the Livewire component.
 */
class UpdateTeamMemberRole
{
    public function update(User $user, Team $team, int $teamMemberId, string $role): void
    {
        Gate::forUser($user)->authorize('updateTeamMember', $team);

        Validator::make(['role' => $role], [
            'role' => ['required', 'string', Rule::in(array_keys(config('teams.roles')))],
        ])->validate();

        $team->users()->updateExistingPivot($teamMemberId, ['role' => $role]);
    }
}
