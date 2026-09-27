<?php

declare(strict_types=1);

use App\Models\User;

test('owners get the owner role and every permission', function (): void {
    $owner = User::factory()->withPersonalTeam()->create();
    $team  = $owner->currentTeam;

    expect($owner->teamRole($team)->key)->toBe('owner')
        ->and($owner->teamPermissions($team))->toBe(['*'])
        ->and($owner->hasTeamPermission($team, 'person:delete'))->toBeTrue();
});

test('members get the role and permissions defined in config', function (): void {
    $editor = $this->memberWithRole('editor');
    $team   = $editor->currentTeam;

    expect($editor->teamRole($team)->name)->toBe('Editor')
        ->and($editor->hasTeamRole($team, 'editor'))->toBeTrue()
        ->and($editor->hasTeamPermission($team, 'person:update'))->toBeTrue()
        ->and($editor->hasTeamPermission($team, 'person:delete'))->toBeFalse();
});

test('users outside a team have no role or permissions on it', function (): void {
    $outsider = User::factory()->withPersonalTeam()->create();
    $team     = User::factory()->withPersonalTeam()->create()->currentTeam;

    expect($outsider->teamRole($team))->toBeNull()
        ->and($outsider->teamRole(null))->toBeNull()
        ->and($outsider->teamPermissions($team))->toBe([])
        ->and($outsider->hasTeamPermission($team, 'person:read'))->toBeFalse();
});
