<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

test('queries slower than the configured threshold are logged', function (): void {
    config(['app.query_logging.slow_threshold' => -1]);

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $message, array $context): bool => $message === 'An individual database query exceeded -1 ms.' && $context['sql'] === 'select 1');

    DB::select('select 1');
});

test('queries faster than the configured threshold are not logged', function (): void {
    config(['app.query_logging.slow_threshold' => 60_000]);

    Log::shouldReceive('warning')->never();

    DB::select('select 1');
});
