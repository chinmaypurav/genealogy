<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Person;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Single source of truth for what a user may do with people of their current team.
 *
 * Every person ability (routes via `->can()`, Livewire actions via `$this->authorize()`,
 * Blade via `@can`) resolves here so permission checks cannot drift between call sites.
 * Abilities on an existing person also require it to belong to the user's current team,
 * so authorization holds even if the Person global team scope is bypassed.
 */
class PersonPolicy
{
    public function view(User $user, Person $person): Response
    {
        return $this->allowIfPermitted($user, 'person:read', $person);
    }

    public function create(User $user): Response
    {
        return $this->allowIfPermitted($user, 'person:create');
    }

    public function update(User $user, Person $person): Response
    {
        return $this->allowIfPermitted($user, 'person:update', $person);
    }

    public function delete(User $user, Person $person): Response
    {
        return $this->allowIfPermitted($user, 'person:delete', $person);
    }

    protected function allowIfPermitted(User $user, string $permission, ?Person $person = null): Response
    {
        if ($person !== null && $person->team_id !== $user->current_team_id) {
            return Response::deny(__('app.unauthorized_access'));
        }

        return $user->hasPermission($permission)
            ? Response::allow()
            : Response::deny(__('app.unauthorized_access'));
    }
}
