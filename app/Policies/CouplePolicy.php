<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Couple;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Single source of truth for what a user may do with couples of their current team.
 *
 * Every couple ability (routes via `->can()`, Livewire actions via `$this->authorize()`,
 * Blade via `@can`) resolves here so permission checks cannot drift between call sites.
 * Abilities on an existing couple also require it to belong to the user's current team,
 * so authorization holds even if the Couple global team scope is bypassed.
 */
class CouplePolicy
{
    public function create(User $user): Response
    {
        return $this->allowIfPermitted($user, 'couple:create');
    }

    public function update(User $user, Couple $couple): Response
    {
        return $this->allowIfPermitted($user, 'couple:update', $couple);
    }

    public function delete(User $user, Couple $couple): Response
    {
        return $this->allowIfPermitted($user, 'couple:delete', $couple);
    }

    protected function allowIfPermitted(User $user, string $permission, ?Couple $couple = null): Response
    {
        if ($couple !== null && $couple->team_id !== $user->current_team_id) {
            return Response::deny(__('app.unauthorized_access'));
        }

        return $user->hasPermission($permission)
            ? Response::allow()
            : Response::deny(__('app.unauthorized_access'));
    }
}
