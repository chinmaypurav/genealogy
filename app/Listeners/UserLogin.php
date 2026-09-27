<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Userlog;
use Illuminate\Auth\Events\Login;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

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

        // Log user location (only in production)
        if (app()->isProduction()) {
            $this->logUserLocation($user->id);
        }
    }

    /**
     * Log the user's location.
     */
    private function logUserLocation(int $userId): void
    {
        // Exclude your own IP without storing or exposing it
        $requestIp = request()->ip();

        // Skip if IP is not available
        if (! $requestIp) {
            return;
        }

        $requestIpHash = hash('sha256', $requestIp);
        $devIpHash     = config('app.dev_ip_hash');

        if ($devIpHash && hash_equals($requestIpHash, $devIpHash)) {
            // Skip logging
            return;
        }

        if (($position = Location::get()) instanceof Position) {
            Userlog::create([
                'user_id'      => $userId,
                'country_name' => $position->countryName,
                'country_code' => $position->countryCode !== null ? mb_strtoupper($position->countryCode) : null,
            ]);
        }
    }
}
