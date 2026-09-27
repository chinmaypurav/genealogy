<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Membership;
use App\Models\Team;
use App\Support\TeamRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Team ownership, membership and permission checks for the User model.
 *
 * Copied from Jetstream's HasTeams so the app owns its teams layer. Callers rely on
 * team owners always having every permission, and on API tokens narrowing a member's permissions.
 */
trait HasTeams
{
    public function isCurrentTeam(Team $team): bool
    {
        return $team->id === $this->current_team_id;
    }

    /**
     * Falls back to the personal team when no current team is set yet.
     *
     * @return BelongsTo<Team, $this>
     */
    public function currentTeam(): BelongsTo
    {
        if (is_null($this->current_team_id) && $this->id && ($personalTeam = $this->personalTeam())) {
            $this->switchTeam($personalTeam);
        }

        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function switchTeam(Team $team): bool
    {
        if (! $this->belongsToTeam($team)) {
            return false;
        }

        $this->forceFill(['current_team_id' => $team->id])->save();

        $this->setRelation('currentTeam', $team);

        return true;
    }

    /**
     * @return Collection<int, Team>
     */
    public function allTeams(): Collection
    {
        return $this->ownedTeams->merge($this->teams)->sortBy('name');
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function ownedTeams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return BelongsToMany<Team, $this, Membership, 'membership'>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, Membership::class)
            ->withPivot('role')
            ->withTimestamps()
            ->as('membership');
    }

    public function personalTeam(): ?Team
    {
        return $this->ownedTeams->where('personal_team', true)->first();
    }

    public function ownsTeam(?Team $team): bool
    {
        if (is_null($team)) {
            return false;
        }

        return $this->id === $team->user_id;
    }

    public function belongsToTeam(?Team $team): bool
    {
        if (is_null($team)) {
            return false;
        }

        return $this->ownsTeam($team) || $this->teams->contains(fn (Team $userTeam): bool => $userTeam->id === $team->id);
    }

    public function teamRole(?Team $team): ?TeamRole
    {
        if (is_null($team)) {
            return null;
        }

        if ($this->ownsTeam($team)) {
            return TeamRole::owner();
        }

        if (! $this->belongsToTeam($team)) {
            return null;
        }

        $role = $this->membershipRole($team);

        return $role ? TeamRole::find($role) : null;
    }

    public function hasTeamRole(?Team $team, string $role): bool
    {
        if ($this->ownsTeam($team)) {
            return true;
        }

        return $this->teamRole($team)?->key === $role;
    }

    /**
     * @return list<string>
     */
    public function teamPermissions(?Team $team): array
    {
        if ($this->ownsTeam($team)) {
            return ['*'];
        }

        if (! $this->belongsToTeam($team)) {
            return [];
        }

        return $this->teamRole($team)->permissions ?? [];
    }

    public function hasTeamPermission(?Team $team, string $permission): bool
    {
        if ($this->ownsTeam($team)) {
            return true;
        }

        if (! $this->belongsToTeam($team)) {
            return false;
        }

        if ($this->currentAccessToken() !== null && ! $this->tokenCan($permission)) {
            return false;
        }

        $permissions = $this->teamPermissions($team);

        return in_array($permission, $permissions, true)
            || in_array('*', $permissions, true)
            || (Str::endsWith($permission, ':create') && in_array('*:create', $permissions, true))
            || (Str::endsWith($permission, ':update') && in_array('*:update', $permissions, true));
    }

    protected function membershipRole(Team $team): ?string
    {
        return $team->users->firstWhere('id', $this->id)?->membership->role;
    }
}
