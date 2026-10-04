<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a person can be created', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $person = Person::factory()
        ->withUser($user)
        ->create();

    $this->assertDatabaseHas('people', [
        'id' => $person->id,
    ]);
});

test('a person can be updated', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $person = Person::factory()
        ->withUser($user)
        ->create();

    $person->update([
        'firstname' => 'Updated',
    ]);

    $this->assertDatabaseHas('people', [
        'id'        => $person->id,
        'firstname' => 'Updated',
    ]);
});

test('a person can be soft deleted', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $person = Person::factory()
        ->withUser($user)
        ->create();

    $person->delete();

    $this->assertSoftDeleted($person);
});

test('a person can be hard deleted', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $person = Person::factory()
        ->withUser($user)
        ->create();

    $person->forceDelete();

    $this->assertDatabaseMissing('people', [
        'id' => $person->id,
    ]);
});

test('returns full name', function (): void {
    $person = Person::factory()->create([
        'firstname' => ' John',
        'surname'   => 'Doe ',
    ]);

    expect($person->name)->toBe('John Doe');
});

test('returns full name even when firstname is missing', function (): void {
    $person = Person::factory()->create([
        'firstname' => null,
        'surname'   => '  Doe ',
    ]);

    expect($person->name)->toBe('Doe');
});

test('similar persons are limited to the given team', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $ownTeamMatch = Person::factory()->create(['team_id' => $user->current_team_id, 'firstname' => 'Johnathan']);

    Person::factory()->create(['firstname' => 'Johnathan']);

    expect(Person::query()->withoutGlobalScopes()->similarTo($user->current_team_id, ['John'])->pluck('id')->all())
        ->toBe([$ownTeamMatch->id]);
});

test('the team scope returns no people when the user has no current team', function (): void {
    Person::factory()->withUser(User::factory()->withPersonalTeam()->create())->create();

    // A dangling current_team_id (e.g. a team removed outside DeleteTeam) resolves to no current team.
    $user = User::factory()->create(['current_team_id' => 999_999]);

    $this->actingAs($user);

    expect($user->currentTeam)->toBeNull()
        ->and(Person::count())->toBe(0);
});
