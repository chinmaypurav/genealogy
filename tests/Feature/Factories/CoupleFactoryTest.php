<?php

declare(strict_types=1);

use App\Models\Couple;
use App\Models\Person;
use App\Models\User;

/*
 * Factories have no app/ counterpart, so this sits under tests/Feature/Factories.
 */

test('creates its own partners instead of reusing existing people', function (): void {
    $existing = Person::factory()->create();

    $couple = Couple::factory()->create();

    expect($couple->person1_id)->not->toBe($couple->person2_id)
        ->and([$couple->person1_id, $couple->person2_id])->not->toContain($existing->id);
});

test('uses the given partners and takes the team from the first partner', function (): void {
    $team    = User::factory()->withPersonalTeam()->create()->currentTeam;
    $person  = Person::factory()->create(['team_id' => $team->id]);
    $partner = Person::factory()->create(['team_id' => $team->id]);

    $couple = Couple::factory()->create(['person1_id' => $person->id, 'person2_id' => $partner->id]);

    expect($couple->person1_id)->toBe($person->id)
        ->and($couple->person2_id)->toBe($partner->id)
        ->and($couple->team_id)->toBe($team->id)
        ->and(Person::withoutGlobalScopes()->count())->toBe(2);
});

test('puts a generated second partner in the first partner\'s team', function (): void {
    $team   = User::factory()->withPersonalTeam()->create()->currentTeam;
    $person = Person::factory()->create(['team_id' => $team->id]);

    $couple = Couple::factory()->create(['person1_id' => $person->id]);

    expect($couple->team_id)->toBe($team->id)
        ->and(Person::withoutGlobalScopes()->find($couple->person2_id)->team_id)->toBe($team->id);
});
