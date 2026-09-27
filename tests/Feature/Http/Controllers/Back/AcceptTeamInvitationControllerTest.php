<?php

declare(strict_types=1);

use App\Models\User;

test('invitation links must be signed', function (): void {
    $owner   = User::factory()->withPersonalTeam()->create();
    $invitee = User::factory()->withPersonalTeam()->create();

    $invitation = $owner->currentTeam->teamInvitations()->create(['email' => $invitee->email, 'role' => 'editor']);

    $this->actingAs($invitee)
        ->get(route('team-invitations.accept', $invitation))
        ->assertForbidden();

    expect($invitee->fresh()->belongsToTeam($owner->currentTeam))->toBeFalse();
});
