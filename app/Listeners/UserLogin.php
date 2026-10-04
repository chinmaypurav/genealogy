<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

/**
 * Applies the user's preferences to the session and records when they were last seen.
 *
 * Hooked to the Login event so every authentication path (password, 2FA, remember-me)
 * gets the same locale/timezone setup without each controller repeating it.
 */
final class UserLogin
{
    /**
     * Handle the login event.
     */
    public function handle(Login $event): void
    {
        /** @var \App\Models\User $user */
        $user = $event->user;

        session([
            'locale'   => $user->language ?? config('app.locale', 'en'),
            'timezone' => $user->timezone ?? config('app.timezone', 'UTC'),
        ]);

        // Update user's last seen timestamp
        $user->timestamps = false;
        $user->seen_at    = \Carbon\Carbon::now();
        $user->saveQuietly();
    }
}
