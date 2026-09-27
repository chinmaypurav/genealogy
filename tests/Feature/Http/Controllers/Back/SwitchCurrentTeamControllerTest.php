<?php

declare(strict_types=1);

use App\Models\User;

test('users can switch to a team they belong to', function (): void {
    $member   = $this->memberWithRole('member');
    $personal = $member->ownedTeams()->create(['name' => 'Personal', 'personal_team' => true]);

    $this->actingAs($member)
        ->put(route('current-team.update'), ['team_id' => $personal->id])
        ->assertRedirect(config('fortify.home'));

    expect($member->fresh()->current_team_id)->toBe($personal->id);
});

test('users cannot switch to a team they do not belong to', function (): void {
    $user  = User::factory()->withPersonalTeam()->create();
    $other = User::factory()->withPersonalTeam()->create()->currentTeam;

    $this->actingAs($user)
        ->put(route('current-team.update'), ['team_id' => $other->id])
        ->assertForbidden();

    expect($user->fresh()->current_team_id)->not->toBe($other->id);
});
