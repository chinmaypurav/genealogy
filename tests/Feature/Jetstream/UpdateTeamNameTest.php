<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('team names can be updated', function (): void {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test('teams::update-team-name-form', ['team' => $user->currentTeam])
        ->set(['state' => ['name' => 'Test Team']])
        ->call('updateTeamName');

    expect($user->fresh()->ownedTeams)->toHaveCount(1);
    expect($user->currentTeam->fresh()->name)->toEqual('Test Team');
});
