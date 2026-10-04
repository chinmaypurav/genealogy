<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;

test('a login stores the user preferences in the session and updates seen_at', function (): void {
    Date::setTestNow('2026-10-04 12:00:00');

    $user = User::factory()->withPersonalTeam()->create([
        'language' => 'nl',
        'timezone' => 'Europe/Brussels',
        'seen_at'  => null,
    ]);

    event(new Login('web', $user, false));

    expect(session('locale'))->toBe('nl')
        ->and(session('timezone'))->toBe('Europe/Brussels')
        ->and($user->fresh()->seen_at->toDateTimeString())->toBe('2026-10-04 12:00:00');
});
