<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    // Disable activity logging only for these tests
    Activity::unsetEventDispatcher();
});

afterEach(function (): void {
    // Restore activity logging after each test
    Activity::setEventDispatcher(app('events'));
});

test('user accounts can be deleted', function (): void {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test('profile::delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $user->refresh();

    expect($user->deleted_at)->not->toBeNull();
});

test('correct password must be provided before account can be deleted', function (): void {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test('profile::delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});
