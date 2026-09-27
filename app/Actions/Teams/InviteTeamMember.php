<?php

declare(strict_types=1);

namespace App\Actions\Teams;

use App\Mail\TeamInvitation;
use App\Models\Team;
use App\Models\TeamInvitation as TeamInvitationModel;
use App\Models\User;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Invites an email address to join a team and emails them a signed accept link.
 */
class InviteTeamMember
{
    /**
     * Invite a new team member to the given team.
     */
    public function invite(User $user, Team $team, string $email, ?string $role = null): void
    {
        Gate::forUser($user)->authorize('addTeamMember', $team);

        $this->validate($team, $email, $role);

        $invitation = $team->teamInvitations()->create([
            'email' => $email,
            'role'  => $role,
        ]);

        Mail::to($email)->send(new TeamInvitation($invitation));

        // Log activity: Invite Team Member
        defer(function () use ($user, $team, $email, $role): void {
            activity()
                ->useLog('user_team')
                ->performedOn($team)
                ->causedBy($user)
                ->event(__('app.event_invited'))
                ->withProperties([
                    'email' => $email,
                    'role'  => $role,
                ])
                ->log(__('team.member') . ' ' . __('app.event_invited'));
        });
    }

    /**
     * Validate the invite member operation.
     */
    protected function validate(Team $team, string $email, ?string $role): void
    {
        Validator::make([
            'email' => $email,
            'role'  => $role,
        ], $this->rules($team), [
            'email.unique' => __('team.user_already_invited'),
        ])->after(
            $this->ensureUserIsNotAlreadyOnTeam($team, $email)
        )->validateWithBag('addTeamMember');
    }

    /**
     * Get the validation rules for inviting a team member.
     *
     * @return array<string, list<mixed>>
     */
    protected function rules(Team $team): array
    {
        return [
            'email' => [
                'required', 'email',
                Rule::unique(TeamInvitationModel::class)->where(function (Builder $query) use ($team): void {
                    $query->where('team_id', $team->id);
                }),
            ],
            'role' => ['required', 'string', Rule::in(array_keys(config('teams.roles')))],
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
