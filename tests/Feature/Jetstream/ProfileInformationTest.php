<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('current profile information is available', function (): void {
    $this->actingAs($user = User::factory()->create());

    $component = Livewire::test('profile::update-profile-information-form');

    expect($component->state['surname'])->toEqual($user->surname);
    expect($component->state['email'])->toEqual($user->email);
});

test('profile information can be updated', function (): void {
    $this->actingAs($user = User::factory()->create());

    Livewire::test('profile::update-profile-information-form')
        ->set('state', [
            'surname'  => 'Test Name',
            'email'    => 'test@example.com',
            'language' => 'en',
            'timezone' => 'UTC',
        ])
        ->call('updateProfileInformation');

    expect($user->fresh()->surname)->toEqual('Test Name');
    expect($user->fresh()->email)->toEqual('test@example.com');
});

test('profile page renders every account section', function (): void {
    $this->actingAs(User::factory()->withPersonalTeam()->create());

    $this->get(route('profile.show'))
        ->assertOk()
        ->assertSeeLivewire('profile::update-profile-information-form')
        ->assertSeeLivewire('profile::update-password-form')
        ->assertSeeLivewire('profile::two-factor-authentication-form')
        ->assertSeeLivewire('profile::logout-other-browser-sessions-form')
        ->assertSeeLivewire('profile::delete-user-form');
});
