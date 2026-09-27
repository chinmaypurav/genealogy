<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Contracts\CreatesTeams;

/**
 * Creates a new, non-personal team for a user and switches the user to it.
 */
class CreateTeam implements CreatesTeams
{
    /**
     * Validate and create a new team for the given user.
     *
     * @param  array<string, string>  $input
     */
    public function create(User $user, array $input): Team
    {
        Gate::forUser($user)->authorize('create', Team::class);

        Validator::make($input, [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ])->validateWithBag('createTeam');

        /** @var Team $team */
        $team = $user->ownedTeams()->create([
            'name'          => $input['name'],
            'description'   => $input['description'] ?? null,
            'personal_team' => false,
        ]);

        $user->switchTeam($team);

        return $team;
    }
}
