<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\Team;

test('members without person permissions cannot open the add and edit pages', function (): void {
    $member = $this->memberWithRole('member');
    $person = Person::factory()->create(['team_id' => $member->current_team_id]);

    $this->actingAs($member);

    $this->get(route('people.add'))->assertForbidden()->assertSee(__('app.unauthorized_access'));
    $this->get(route('people.add-child', $person))->assertForbidden();
    $this->get(route('people.edit-profile', $person))->assertForbidden();
});

test('editors can open the add and edit pages', function (): void {
    $editor = $this->memberWithRole('editor');
    $person = Person::factory()->create(['team_id' => $editor->current_team_id]);

    $this->actingAs($editor);

    $this->get(route('people.add'))->assertOk();
    $this->get(route('people.add-child', $person))->assertOk();
    $this->get(route('people.edit-profile', $person))->assertOk();
});

test('only members with delete permission can delete a person', function (): void {
    $editor  = $this->memberWithRole('editor');
    $manager = $this->memberWithRole('manager');

    expect($editor->can('delete', Person::factory()->create(['team_id' => $editor->current_team_id])))->toBeFalse()
        ->and($manager->can('delete', Person::factory()->create(['team_id' => $manager->current_team_id])))->toBeTrue();
});

test('people of another team are denied even when the team scope is bypassed', function (): void {
    $manager  = $this->memberWithRole('manager');
    $outsider = Person::factory()->create(['team_id' => Team::factory()]);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect($manager->can($ability, $outsider))->toBeFalse();
    }
});
