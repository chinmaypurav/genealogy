<?php

declare(strict_types=1);

use App\Actions\Teams\AddTeamMember;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('adds a user with a role from the teams config', function (): void {
    $owner  = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->create();

    app(AddTeamMember::class)->add($owner, $owner->currentTeam, $member->email, 'manager');

    expect($member->fresh()->hasTeamRole($owner->currentTeam->fresh(), 'manager'))->toBeTrue();
});

test('rejects roles that are not in the teams config', function (): void {
    $owner  = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->create();

    app(AddTeamMember::class)->add($owner, $owner->currentTeam, $member->email, 'owner');
})->throws(ValidationException::class);
