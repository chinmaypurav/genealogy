<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('sessions are logged out after the password changes elsewhere', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user, 'web')->get(route('profile.show'))->assertOk();

    $user->forceFill(['password' => Hash::make('changed-elsewhere')])->save();

    $this->get(route('profile.show'))->assertRedirect(route('login'));

    $this->assertGuest('web');
});
