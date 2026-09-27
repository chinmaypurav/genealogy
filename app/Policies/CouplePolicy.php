<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Couple;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Maps couple abilities to the team permissions of the current team (see config/teams.php).
 *
 * Lives in a policy so routes can authorize with `->can()` middleware instead of every
 * controller action repeating the same permission check. Team isolation is not handled
 * here: the Couple global team scope already hides couples from other teams.
 */
class CouplePolicy
{
    public function create(User $user): Response
    {
        return $this->allowIfPermitted($user, 'couple:create');
    }

    public function update(User $user, Couple $couple): Response
    {
        return $this->allowIfPermitted($user, 'couple:update');
    }

    protected function allowIfPermitted(User $user, string $permission): Response
    {
        return $user->hasPermission($permission)
            ? Response::allow()
            : Response::deny(__('app.unauthorized_access'));
    }
}
