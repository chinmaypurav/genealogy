<?php

declare(strict_types=1);

use App\Models\Couple;
use App\Models\Person;
use App\Models\Team;

test('members without couple permissions cannot add or edit partners', function (): void {
    $member = $this->memberWithRole('member');
    $person = Person::factory()->create(['team_id' => $member->current_team_id]);
    $couple = Couple::factory()->create(['person1_id' => $person->id]);

    $this->actingAs($member);

    $this->get(route('people.add-partner', $person))->assertForbidden();
    $this->get(route('people.edit-partner', [$person, $couple]))->assertForbidden();
});

test('editors can add and edit partners', function (): void {
    $editor = $this->memberWithRole('editor');
    $person = Person::factory()->create(['team_id' => $editor->current_team_id]);
    $couple = Couple::factory()->create(['person1_id' => $person->id]);

    $this->actingAs($editor);

    $this->get(route('people.add-partner', $person))->assertOk();
    $this->get(route('people.edit-partner', [$person, $couple]))->assertOk();
});

test('only members with delete permission can delete a couple', function (): void {
    $editor  = $this->memberWithRole('editor');
    $manager = $this->memberWithRole('manager');

    expect($editor->can('delete', Couple::factory()->create(['team_id' => $editor->current_team_id])))->toBeFalse()
        ->and($manager->can('delete', Couple::factory()->create(['team_id' => $manager->current_team_id])))->toBeTrue();
});

test('couples of another team are denied even when the team scope is bypassed', function (): void {
    $manager  = $this->memberWithRole('manager');
    $outsider = Couple::factory()->create(['team_id' => Team::factory()]);

    expect($manager->can('update', $outsider))->toBeFalse()
        ->and($manager->can('delete', $outsider))->toBeFalse();
});
