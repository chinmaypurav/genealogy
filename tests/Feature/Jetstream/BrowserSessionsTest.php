<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('other browser sessions can be logged out', function (): void {
    $this->actingAs($user = User::factory()->create());

    Livewire::test('profile::logout-other-browser-sessions-form')
        ->set('password', 'password')
        ->call('logoutOtherBrowserSessions')
        ->assertSuccessful();
});

test('lists database sessions and removes the other ones on logout', function (): void {
    config(['session.driver' => 'database']);

    $this->actingAs($user = User::factory()->create());

    DB::table('sessions')->insert([
        'id'            => 'other-session',
        'user_id'       => $user->id,
        'ip_address'    => '203.0.113.5',
        'user_agent'    => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        'payload'       => '',
        'last_activity' => now()->subHour()->timestamp,
    ]);

    $component = Livewire::test('profile::logout-other-browser-sessions-form');

    $session = $component->instance()->sessions->first();

    expect($session->ip_address)->toBe('203.0.113.5')
        ->and($session->agent->browser())->toBe('Chrome')
        ->and($session->is_current_device)->toBeFalse();

    $component->set('password', 'password')->call('logoutOtherBrowserSessions')->assertHasNoErrors();

    expect(DB::table('sessions')->where('id', 'other-session')->exists())->toBeFalse();
});

test('logging out other sessions requires the current password', function (): void {
    config(['session.driver' => 'database']);

    $this->actingAs(User::factory()->create());

    Livewire::test('profile::logout-other-browser-sessions-form')
        ->set('password', 'wrong-password')
        ->call('logoutOtherBrowserSessions')
        ->assertHasErrors(['password']);
});
