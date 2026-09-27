<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Date;
use Laravel\Fortify\Features;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('two factor authentication can be enabled', function (): void {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two factor authentication is not enabled.');
    }

    $this->actingAs($user = User::factory()->create());

    $this->withSession(['auth.password_confirmed_at' => Date::now()->getTimestamp()]);

    Livewire::test('profile::two-factor-authentication-form')
        ->call('enableTwoFactorAuthentication');

    $user = $user->fresh();

    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->recoveryCodes())->toHaveCount(8);
});

test('recovery codes can be regenerated', function (): void {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two factor authentication is not enabled.');
    }

    $this->actingAs($user = User::factory()->create());

    $this->withSession(['auth.password_confirmed_at' => Date::now()->getTimestamp()]);

    $component = Livewire::test('profile::two-factor-authentication-form')
        ->call('enableTwoFactorAuthentication')
        ->call('regenerateRecoveryCodes');

    $user = $user->fresh();

    $component->call('regenerateRecoveryCodes');

    expect($user->recoveryCodes())->toHaveCount(8);
    expect(array_diff($user->recoveryCodes(), $user->fresh()->recoveryCodes()))->toHaveCount(8);
});

test('two factor authentication can be disabled', function (): void {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two factor authentication is not enabled.');
    }

    $this->actingAs($user = User::factory()->create());

    $this->withSession(['auth.password_confirmed_at' => Date::now()->getTimestamp()]);

    $component = Livewire::test('profile::two-factor-authentication-form')
        ->call('enableTwoFactorAuthentication');

    expect($user->fresh()->two_factor_secret)->not->toBeNull();

    $component->call('disableTwoFactorAuthentication');

    expect($user->fresh()->two_factor_secret)->toBeNull();
});
