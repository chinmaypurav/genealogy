<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;
use Override;

/**
 * Keeps Jetstream from registering its own routes while the last screens move off the package (#21).
 */
final class JetstreamServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        // Routes are declared in routes/web.php so they can move off Jetstream one screen at a time.
        Jetstream::ignoreRoutes();
    }
}
