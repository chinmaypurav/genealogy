<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Models\Team;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Jetstream\Contracts\AddsTeamMembers;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Adds an existing user to a team with a role, from the team settings or an accepted invitation.
 *
 * Kept as an action because both entry points share its authorization, validation and activity log.
 */
class AddTeamMember implements AddsTeamMembers
{
    /**
     * Add a new team member to the given team.
     */
    public function add(User $user, Team $team, string $email, ?string $role = null): RedirectResponse
    {
        Gate::forUser($user)->authorize('addTeamMember', $team);

        $this->validate($team, $email, $role);

        $newTeamMember = User::where('email', $email)->firstOrFail();

        $team->users()->attach(
            $newTeamMember, ['role' => $role]
        );

        // Log activity: Added Team Member
        defer(function () use ($user, $team, $newTeamMember, $role): void {
            activity()
                ->useLog('user_team')
                ->performedOn($team)
                ->causedBy($user)
                ->event(__('app.event_added'))
                ->withProperties([
                    'email' => $newTeamMember->email,
                    'name'  => $newTeamMember->name,
                    'role'  => $role,
                ])
                ->log(__('team.member') . ' ' . __('app.event_added'));
        });

        return redirect()->route('teams.show', $team);
    }

    /**
     * Validate the add member operation.
     */
    protected function validate(Team $team, string $email, ?string $role): void
    {
        Validator::make([
            'email' => $email,
            'role'  => $role,
        ], $this->rules(), [
            'email.exists' => __('team.user_not_found'),
        ])->after(
            $this->ensureUserIsNotAlreadyOnTeam($team, $email)
        )->validateWithBag('addTeamMember');
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users'],
            'role'  => ['required', 'string', Rule::in(array_keys(config('teams.roles')))],
        ];
    }

    /**
     * Ensure that the user is not already on the team.
     */
    protected function ensureUserIsNotAlreadyOnTeam(Team $team, string $email): Closure
    {
        return function ($validator) use ($team, $email): void {
            $validator->errors()->addIf(
                $team->hasUserWithEmail($email),
                'email',
                __('team.user_already_in_team')
            );
        };
    }
}
