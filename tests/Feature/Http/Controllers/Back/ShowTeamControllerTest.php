<?php

declare(strict_types=1);

use App\Models\User;

test('team members can open the team settings page', function (): void {
    $member = $this->memberWithRole('member');

    $this->actingAs($member)
        ->get(route('teams.show', $member->currentTeam))
        ->assertOk()
        ->assertSeeLivewire('teams::update-team-name-form')
        ->assertSeeLivewire('teams::team-member-manager');
});

test('users outside the team cannot open its settings page', function (): void {
    $team = User::factory()->withPersonalTeam()->create()->currentTeam;

    $this->actingAs(User::factory()->withPersonalTeam()->create())
        ->get(route('teams.show', $team))
        ->assertForbidden();
});

test('the create team page renders', function (): void {
    $this->actingAs(User::factory()->withPersonalTeam()->create())
        ->get(route('teams.create'))
        ->assertOk()
        ->assertSeeLivewire('teams::create-team-form');
});
