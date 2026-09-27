<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Person;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Maps person abilities to the team permissions of the current team (see config/teams.php).
 *
 * Lives in a policy so routes can authorize with `->can()` middleware instead of every
 * controller action repeating the same permission check. Team isolation is not handled
 * here: the Person global team scope already hides people from other teams.
 */
class PersonPolicy
{
    public function create(User $user): Response
    {
        return $this->allowIfPermitted($user, 'person:create');
    }

    public function update(User $user, Person $person): Response
    {
        return $this->allowIfPermitted($user, 'person:update');
    }

    protected function allowIfPermitted(User $user, string $permission): Response
    {
        return $user->hasPermission($permission)
            ? Response::allow()
            : Response::deny(__('app.unauthorized_access'));
    }
}
