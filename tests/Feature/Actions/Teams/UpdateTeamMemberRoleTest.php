<?php

declare(strict_types=1);

use App\Actions\Teams\UpdateTeamMemberRole;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

test('rejects roles that are not in the teams config', function (): void {
    $member = $this->memberWithRole('editor');
    $team   = $member->currentTeam;

    app(UpdateTeamMemberRole::class)->update($team->owner, $team, $member->id, 'owner');
})->throws(ValidationException::class);

test('only users allowed to manage members can change roles', function (): void {
    $member = $this->memberWithRole('editor');
    $team   = $member->currentTeam;
    $other  = User::factory()->create();
    $team->users()->attach($other, ['role' => 'member']);

    app(UpdateTeamMemberRole::class)->update($member, $team, $other->id, 'administrator');
})->throws(AuthorizationException::class);
