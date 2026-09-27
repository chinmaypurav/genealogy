<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Session\Middleware\AuthenticateSession as BaseAuthenticateSession;
use Override;

/**
 * Logs out other sessions after a password change, checking against the web session guard.
 *
 * Laravel's version uses the default guard, which `auth:sanctum` switches to Sanctum's request guard;
 * that guard has no session to check.
 */
class AuthenticateSession extends BaseAuthenticateSession
{
    #[Override]
    protected function guard(): StatefulGuard
    {
        return app(StatefulGuard::class);
    }
}
