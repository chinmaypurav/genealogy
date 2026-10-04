<?php

declare(strict_types=1);

use App\Models\Team;
use App\Models\User;
use App\Notifications\OwnershipTransferred;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;
use Spatie\Activitylog\Models\Activity;

// Helper: create a non-personal team owned by $owner
function makeNonPersonalTeam(User $owner): Team
{
    /** @var Team $team */
    $team = Team::forceCreate([
        'user_id'       => $owner->id,
        'name'          => 'Test Team',
        'personal_team' => false,
    ]);

    $owner->forceFill(['current_team_id' => $team->id])->save();

    return $team;
}

test('owner can transfer ownership to an existing team member', function (): void {
    Notification::fake();

    $owner  = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();
    $team   = makeNonPersonalTeam($owner);

    $team->users()->attach($member->id, ['role' => 'editor']);

    $this->actingAs($owner)
        ->put(route('teams.transfer-ownership', $team), [
            'new_owner_id' => $member->id,
        ])
        ->assertRedirect();

    $team->refresh();

    expect($team->user_id)->toBe($member->id)
        ->and($team->users()->whereKey($member->id)->exists())->toBeFalse()
        ->and($team->users()->find($owner->id)->membership->role)->toBe('administrator')
        ->and(Activity::query()->where('event', __('app.event_transferred'))->whereMorphedTo('subject', $team)->exists())->toBeTrue();

    Notification::assertSentTo($member, OwnershipTransferred::class);
});

test('a failed transfer is reported and has no side effects', function (): void {
    Notification::fake();
    Exceptions::fake();

    $owner  = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();
    $team   = makeNonPersonalTeam($owner);

    $team->users()->attach($member->id, ['role' => 'editor']);

    Team::saving(fn (): never => throw new RuntimeException('Transfer failed'));

    $this->actingAs($owner)
        ->put(route('teams.transfer-ownership', $team), [
            'new_owner_id' => $member->id,
        ])
        ->assertRedirect();

    $team->refresh();

    expect($team->user_id)->toBe($owner->id)
        ->and($team->users()->whereKey($member->id)->exists())->toBeTrue()
        ->and(Activity::query()->where('event', __('app.event_transferred'))->exists())->toBeFalse();

    Notification::assertNothingSent();
    Exceptions::assertReported(RuntimeException::class);
});

test('a notification failure does not undo a committed transfer', function (): void {
    Exceptions::fake();

    $owner  = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();
    $team   = makeNonPersonalTeam($owner);

    $team->users()->attach($member->id, ['role' => 'editor']);

    Notification::shouldReceive('send')->andThrow(new RuntimeException('Mail server down'));

    $this->actingAs($owner)
        ->put(route('teams.transfer-ownership', $team), [
            'new_owner_id' => $member->id,
        ])
        ->assertRedirect();

    expect($team->fresh()->user_id)->toBe($member->id);

    Exceptions::assertReported(RuntimeException::class);
});

// Guard 1 — only the current owner may initiate a transfer

test('non-owner member cannot transfer ownership', function (): void {
    $owner  = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();
    $team   = makeNonPersonalTeam($owner);

    $team->users()->attach($member->id, ['role' => 'editor']);

    $this->actingAs($member)
        ->put(route('teams.transfer-ownership', $team), [
            'new_owner_id' => $member->id,
        ])
        ->assertForbidden();

    expect($team->fresh()->user_id)->toBe($owner->id);
});

test('unauthenticated user cannot transfer ownership', function (): void {
    $owner = User::factory()->withPersonalTeam()->create();
    $team  = makeNonPersonalTeam($owner);

    $this->put(route('teams.transfer-ownership', $team), [
        'new_owner_id' => $owner->id,
    ])->assertRedirect(route('login'));

    expect($team->fresh()->user_id)->toBe($owner->id);
});

test('unrelated authenticated user cannot transfer ownership of another team', function (): void {
    $owner    = User::factory()->withPersonalTeam()->create();
    $attacker = User::factory()->withPersonalTeam()->create();
    $team     = makeNonPersonalTeam($owner);

    // Attacker has no relationship to this team at all
    $this->actingAs($attacker)
        ->put(route('teams.transfer-ownership', $team), [
            'new_owner_id' => $attacker->id,
        ])
        ->assertForbidden();

    expect($team->fresh()->user_id)->toBe($owner->id);
});

// Guard 2 — personal teams cannot be transferred

test('personal team cannot be transferred', function (): void {
    $owner = User::factory()->withPersonalTeam()->create();

    // The personal team created by withPersonalTeam()
    $personalTeam = $owner->ownedTeams()->where('personal_team', true)->firstOrFail();

    $other = User::factory()->withPersonalTeam()->create();
    $personalTeam->users()->attach($other->id, ['role' => 'editor']);

    $this->actingAs($owner)
        ->put(route('teams.transfer-ownership', $personalTeam), [
            'new_owner_id' => $other->id,
        ])
        ->assertForbidden();

    expect($personalTeam->fresh()->user_id)->toBe($owner->id);
});

// Guard 3 — new owner must already be a team member

test('cannot transfer ownership to a user who is not a team member', function (): void {
    $owner    = User::factory()->withPersonalTeam()->create();
    $outsider = User::factory()->withPersonalTeam()->create();
    $team     = makeNonPersonalTeam($owner);

    // $outsider is a valid user but has no relationship to $team
    $this->actingAs($owner)
        ->put(route('teams.transfer-ownership', $team), [
            'new_owner_id' => $outsider->id,
        ])
        ->assertSessionHasErrors('new_owner_id');

    expect($team->fresh()->user_id)->toBe($owner->id);
});

test('cannot transfer ownership to the current owner', function (): void {
    $owner = User::factory()->withPersonalTeam()->create();
    $team  = makeNonPersonalTeam($owner);

    // A former owner keeps a pivot row, so the owner can also appear as a member.
    $team->users()->attach($owner->id, ['role' => 'administrator']);

    $this->actingAs($owner)
        ->put(route('teams.transfer-ownership', $team), [
            'new_owner_id' => $owner->id,
        ])
        ->assertSessionHasErrors('new_owner_id');
});

test('new_owner_id must be present', function (): void {
    $owner = User::factory()->withPersonalTeam()->create();
    $team  = makeNonPersonalTeam($owner);

    $this->actingAs($owner)
        ->put(route('teams.transfer-ownership', $team), [])
        ->assertSessionHasErrors('new_owner_id');
});

test('new_owner_id must reference an existing user', function (): void {
    $owner = User::factory()->withPersonalTeam()->create();
    $team  = makeNonPersonalTeam($owner);

    $this->actingAs($owner)
        ->put(route('teams.transfer-ownership', $team), [
            'new_owner_id' => 99999,
        ])
        ->assertSessionHasErrors('new_owner_id');
});

test('a former owner keeps their existing membership role', function (): void {
    $owner  = User::factory()->withPersonalTeam()->create();
    $member = User::factory()->withPersonalTeam()->create();
    $team   = makeNonPersonalTeam($owner);

    $team->users()->attach($owner->id, ['role' => 'manager']);
    $team->users()->attach($member->id, ['role' => 'editor']);

    $this->actingAs($owner)
        ->put(route('teams.transfer-ownership', $team), [
            'new_owner_id' => $member->id,
        ])
        ->assertRedirect();

    expect($team->users()->find($owner->id)->membership->role)->toBe('manager');
});
