<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('teams can be created', function (): void {
    $this->actingAs($user = User::factory()->withPersonalTeam()->create());

    Livewire::test('teams::create-team-form')
        ->set(['state' => ['name' => 'Test Team']])
        ->call('createTeam');

    expect($user->fresh()->ownedTeams)->toHaveCount(2);
    expect($user->fresh()->ownedTeams()->latest('id')->first()->name)->toEqual('Test Team');
});
