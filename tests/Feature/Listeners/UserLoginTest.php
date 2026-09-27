<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Stevebauman\Location\Facades\Location;
use Stevebauman\Location\Position;

beforeEach(function (): void {
    $this->app->detectEnvironment(fn (): string => 'production');
});

test('a login in production stores the resolved location', function (): void {
    $user     = User::factory()->withPersonalTeam()->create();
    $position = new Position;

    $position->countryName = 'Belgium';
    $position->countryCode = 'be';

    Location::fake(['*' => $position]);

    event(new Login('web', $user, false));

    $this->assertDatabaseHas('userlogs', [
        'user_id'      => $user->id,
        'country_name' => 'Belgium',
        'country_code' => 'BE',
    ]);
});

test('a login in production stores no location when the country code is unknown', function (): void {
    $user     = User::factory()->withPersonalTeam()->create();
    $position = new Position;

    Location::fake(['*' => $position]);

    event(new Login('web', $user, false));

    $this->assertDatabaseHas('userlogs', [
        'user_id'      => $user->id,
        'country_name' => null,
        'country_code' => null,
    ]);
});

test('a login in production is not logged when the location cannot be resolved', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    Location::fake();

    event(new Login('web', $user, false));

    $this->assertDatabaseMissing('userlogs', ['user_id' => $user->id]);
});
